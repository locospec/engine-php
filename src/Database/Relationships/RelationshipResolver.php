<?php

namespace Locospec\LCS\Database\Relationships;

use Locospec\LCS\Database\DatabaseOperationsCollection;
use Locospec\LCS\Models\ModelDefinition;
use Locospec\LCS\Models\Relationships\BelongsTo;
use Locospec\LCS\Models\Relationships\HasMany;
use Locospec\LCS\Models\Relationships\HasOne;
use Locospec\LCS\Models\Relationships\Relationship;
use Locospec\LCS\Registry\RegistryManager;

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

    public function resolveFilters(array $operation): array
    {
        if (! isset($operation['filters'])) {
            return $operation;
        }

        $operation['filters'] = $this->resolveFilterGroup($operation['filters']);

        return $operation;
    }

    private function resolveFilterGroup(array $group): array
    {
        $resolvedConditions = [];

        foreach ($group['conditions'] as $condition) {
            if (isset($condition['conditions'])) {
                $resolvedConditions[] = $this->resolveFilterGroup($condition);

                continue;
            }

            $resolved = $this->resolveCondition($condition);
            $resolvedConditions = array_merge($resolvedConditions, $resolved);
        }

        return [
            'op' => $group['op'],
            'conditions' => $resolvedConditions,
        ];
    }

    private function resolveCondition(array $condition): array
    {
        $path = explode('.', $condition['attribute']);

        // Not a relationship path
        if (count($path) === 1) {
            return [$condition];
        }

        // This is a relationship path at this point
        // We will try to extract relations which needs resolving
        $relations = [];

        // Remove the last element from path, as it's not a model
        $targetAttribute = array_pop($path);
        $relatedModelNames = $path;
        $currentSourceName = $this->model->getName();

        foreach ($relatedModelNames as $relatedModelName) {
            $sourceModel = $this->registryManager->get('model', $currentSourceName);
            $targetModel = $this->registryManager->get('model', $relatedModelName);
            $relationship = $sourceModel->getRelationship($relatedModelName);
            $extractAndPointAttributes = $this->getExtractAndPointAttributes($relationship);

            // dump($extractAndPointAttributes);

            $relations[] = [
                'source_model_name' => $currentSourceName,
                'target_model_name' => $relatedModelName,
                'source_model' => $sourceModel,
                'target_model' => $targetModel,
                'relationship' => $relationship,
                'extract_attribute' => $extractAndPointAttributes['extract'],
                'point_attribute' => $extractAndPointAttributes['point'],
            ];
            $currentSourceName = $relatedModelName;
        }

        // Reverse relations so that we can resolve
        $relations = array_reverse($relations);

        $currentValue = $condition['value'];

        for ($i = 0; $i < count($relations); $i++) {
            $relation = $relations[$i];
            $targetModel = $relation['target_model'];
            // First we make query on the target model
            $selectOp = [
                'type' => 'select',
                'tableName' => $targetModel->getConfig()->getTable(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $targetAttribute,
                            'op' => $condition['op'],
                            'value' => $currentValue
                        ]
                    ]
                ],
                'attributes' => [$relation['extract_attribute']]
            ];

            $dbOpsResponse = $this->dbOps->add($selectOp)->execute();

            $currentValue = array_column(
                $dbOpsResponse['result'],
                $relation['extract_attribute']
            );

            $targetAttribute = $relation['point_attribute'];
        }

        return [[
            'attribute' => $targetAttribute,
            'op' => $condition['op'],
            'value' => $currentValue,
        ]];
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        if ($relationship instanceof BelongsTo) {
            return ['extract' => $relationship->getOwnerKey(), 'point' => $relationship->getForeignKey()];
        } else if ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return ['extract' => $relationship->getForeignKey(), 'point' => $relationship->getLocalKey()];
        }
    }
}
