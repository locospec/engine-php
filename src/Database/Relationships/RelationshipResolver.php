<?php

namespace Locospec\Engine\Database\Relationships;

use Locospec\Engine\Database\DatabaseOperationsCollection;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Models\Relationships\BelongsTo;
use Locospec\Engine\Models\Relationships\HasMany;
use Locospec\Engine\Models\Relationships\HasOne;
use Locospec\Engine\Models\Relationships\Relationship;
use Locospec\Engine\Registry\RegistryManager;

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

        // dd($relatedModelNames);

        foreach ($relatedModelNames as $relatedModelName) {
            $sourceModel = $this->registryManager->get('model', $currentSourceName);
            // dump($sourceModel);
            $relationship = $sourceModel->getRelationship($relatedModelName);
            // dump($relationship);
            $targetModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
            // dump($targetModel);

            $extractAndPointAttributes = $this->getExtractAndPointAttributes($relationship);

            // dump($extractAndPointAttributes);

            $relations[] = [
                'source_model_name' => $currentSourceName,
                'target_model_name' => $targetModel->getName(),
                'source_model' => $sourceModel,
                'target_model' => $targetModel,
                'relationship' => $relationship,
                'extract_attribute' => $extractAndPointAttributes['extract'],
                'target_attribute' => $extractAndPointAttributes['point'],
                'op' => $extractAndPointAttributes['op'],
            ];

            $currentSourceName = $targetModel->getName();
        }

        // Reverse relations so that we can resolve
        $relations = array_reverse($relations);

        $currentValue = $condition['value'];
        $targetOp = $condition['op'];

        for ($i = 0; $i < count($relations); $i++) {
            $relation = $relations[$i];

            $targetModel = $relation['target_model'];

            // First we make query on the target model
            $selectOp = [
                'type' => 'select',
                'modelName' => $targetModel->getName(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $targetAttribute,
                            'op' => $targetOp,
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
            $targetOp = $relation['op'];
        }

        return [[
            'attribute' => $targetAttribute,
            'op' => $targetOp,
            'value' => $currentValue,
        ]];
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        // If relationship is BelongsTo, we extract ownerKey, and point the values to foreignKey

        // If relationship is HasMany or HasOne, we extract foreignKey, and point the values to localKey

        if ($relationship instanceof BelongsTo) {
            return ['extract' => $relationship->getOwnerKey(), 'point' => $relationship->getForeignKey(), 'op' => 'in'];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return ['extract' => $relationship->getForeignKey(), 'point' => $relationship->getLocalKey(), 'op' => 'in'];
        }
    }
}
