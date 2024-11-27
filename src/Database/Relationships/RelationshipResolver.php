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

        // Get target attribute
        $targetAttribute = array_pop($path);

        // Traverse path in reverse to build queries
        $currentValue = $condition['value'];
        $path = array_reverse($path);
        $path[] = $this->model->getName();
        $models = $path;
        $relationship = null;

        // dd($models);

        for ($i = 0; $i < count($models); $i++) {

            if (! isset($models[$i + 1])) {
                continue;
            }

            $relatedModelName = $models[$i];
            $currentModelName = $models[$i + 1];

            $relatedModel = $this->registryManager->get('model', $relatedModelName);
            $currentModel = $this->registryManager->get('model', $currentModelName);

            // dump($relatedModelName, $currentModelName, $currentModel);

            $relationship = $currentModel->getRelationship($relatedModelName);

            dump($relationship);

            $selectOp = [
                'type' => 'select',
                'tableName' => $relatedModel->getConfig()->getTable(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $targetAttribute,
                            'op' => $condition['op'],
                            'value' => $currentValue,
                        ],
                    ],
                ],
            ];

            dump($targetAttribute);
            dump($selectOp);

            $result = $this->dbOps->add($selectOp)->execute();

            dump(['Extract', $this->getCurrentValueResolverKey($relationship)]);

            $currentValue = array_column(
                $result['result'],
                $this->getCurrentValueResolverKey($relationship)
            );

            $targetAttribute = $relationship->getForeignKey();

            dump('-----------------------');
        }

        // dump("-----------Resolved Relationships------------");

        // dump($relationship);

        return [[
            'attribute' => $this->getCurrentValueResolverKey($relationship),
            'op' => $condition['op'],
            'value' => $currentValue,
        ]];
    }

    private function getCurrentValueResolverKey(Relationship $relationship): string
    {
        if ($relationship instanceof BelongsTo) {
            return $relationship->getOwnerKey();
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return $relationship->getLocalKey();
        }
    }
}
