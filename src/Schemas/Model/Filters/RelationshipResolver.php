<?php

namespace LCSEngine\Schemas\Model\Filters;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Models\ModelDefinition;
use LCSEngine\Models\Relationships\BelongsTo;
use LCSEngine\Models\Relationships\HasMany;
use LCSEngine\Models\Relationships\HasOne;
use LCSEngine\Models\Relationships\Relationship;
use LCSEngine\Registry\RegistryManager;

class RelationshipResolver
{
    private ModelDefinition $model;
    private DatabaseOperationsCollection $dbOps;
    private RegistryManager $registryManager;

    public function __construct(
        ModelDefinition $model,
        DatabaseOperationsCollection $dbOps,
        RegistryManager $registryManager
    ) {
        $this->model = $model;
        $this->dbOps = $dbOps;
        $this->registryManager = $registryManager;
    }

    public function resolve(Filters $filters): Filters
    {
        $root = $filters->getRoot();
        
        if ($root instanceof Condition) {
            return new Filters($this->resolveCondition($root));
        }
        
        if ($root instanceof FilterGroup) {
            return new Filters($this->resolveGroup($root));
        }
        
        if ($root instanceof PrimitiveFilterSet) {
            return new Filters($this->resolvePrimitiveSet($root));
        }
        
        return $filters;
    }

    private function resolveCondition(Condition $condition): Condition|FilterGroup
    {
        $path = explode('.', $condition->getAttribute());

        // Not a relationship path
        if (count($path) === 1) {
            return $condition;
        }

        // This is a relationship path
        $relations = [];
        $targetAttribute = array_pop($path);
        $relatedModelNames = $path;
        $currentSourceName = $this->model->getName();

        foreach ($relatedModelNames as $relatedModelName) {
            $sourceModel = $this->registryManager->get('model', $currentSourceName);
            $relationship = $sourceModel->getRelationship($relatedModelName);
            $targetModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

            $extractAndPointAttributes = $this->getExtractAndPointAttributes($relationship);

            $relations[] = [
                'source_model_name' => $currentSourceName,
                'target_model_name' => $targetModel->getName(),
                'source_model' => $sourceModel,
                'target_model' => $targetModel,
                'relationship' => $relationship,
                'extract_attribute' => $extractAndPointAttributes['extract'],
                'target_attribute' => $extractAndPointAttributes['point'],
                'operator' => $extractAndPointAttributes['operator'],
            ];

            $currentSourceName = $targetModel->getName();
        }

        // Reverse relations for resolution
        $relations = array_reverse($relations);

        $currentValue = $condition->getValue();
        $targetOperator = $condition->getOperator();

        for ($i = 0; $i < count($relations); $i++) {
            $relation = $relations[$i];
            $targetModel = $relation['target_model'];

            // Create select operation for the target model
            $selectOp = [
                'type' => 'select',
                'modelName' => $targetModel->getName(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $targetAttribute,
                            'op' => $targetOperator->value,
                            'value' => $currentValue,
                        ],
                    ],
                ],
                'attributes' => [$relation['extract_attribute']],
            ];

            $dbOpsResponse = $this->dbOps->add($selectOp)->execute();

            $currentValue = array_column(
                $dbOpsResponse[0]['result'],
                $relation['extract_attribute']
            );

            $targetAttribute = $relation['target_attribute'];
            $targetOperator = $relation['operator'];
        }

        // Create a new condition with resolved values
        return new Condition(
            $targetAttribute,
            $targetOperator,
            $currentValue
        );
    }

    private function resolveGroup(FilterGroup $group): FilterGroup
    {
        $resolvedGroup = new FilterGroup($group->getOperator());
        
        foreach ($group->getConditions() as $condition) {
            if ($condition instanceof Condition) {
                $resolved = $this->resolveCondition($condition);
                if ($resolved instanceof FilterGroup) {
                    foreach ($resolved->getConditions() as $resolvedCondition) {
                        $resolvedGroup->add($resolvedCondition);
                    }
                } else {
                    $resolvedGroup->add($resolved);
                }
            } elseif ($condition instanceof FilterGroup) {
                $resolvedGroup->add($this->resolveGroup($condition));
            } elseif ($condition instanceof PrimitiveFilterSet) {
                $resolvedGroup->add($this->resolvePrimitiveSet($condition));
            }
        }
        
        return $resolvedGroup;
    }

    private function resolvePrimitiveSet(PrimitiveFilterSet $set): PrimitiveFilterSet
    {
        $resolvedSet = new PrimitiveFilterSet();
        
        foreach ($set->getFilters() as $key => $value) {
            $condition = new Condition($key, ComparisonOperator::IS, $value);
            $resolved = $this->resolveCondition($condition);
            
            if ($resolved instanceof FilterGroup) {
                foreach ($resolved->getConditions() as $resolvedCondition) {
                    $resolvedSet->add(
                        $resolvedCondition->getAttribute(),
                        $resolvedCondition->getValue()
                    );
                }
            } else {
                $resolvedSet->add(
                    $resolved->getAttribute(),
                    $resolved->getValue()
                );
            }
        }
        
        return $resolvedSet;
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        if ($relationship instanceof BelongsTo) {
            return [
                'extract' => $relationship->getOwnerKey(),
                'point' => $relationship->getForeignKey(),
                'operator' => ComparisonOperator::IS_ANY_OF
            ];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return [
                'extract' => $relationship->getForeignKey(),
                'point' => $relationship->getLocalKey(),
                'operator' => ComparisonOperator::IS_ANY_OF
            ];
        }
    }
} 