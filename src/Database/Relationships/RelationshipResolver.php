<?php

namespace Locospec\LCS\Database\Relationships;

use Locospec\LCS\Database\DatabaseOperationsCollection;
use Locospec\LCS\Models\ModelDefinition;
use Locospec\LCS\Models\Relationships\BelongsTo;
use Locospec\LCS\Models\Relationships\HasMany;
use Locospec\LCS\Models\Relationships\HasOne;
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
        $path = RelationshipPath::parse($condition['attribute']);

        if (! $path->isRelationshipPath()) {
            return [$condition];
        }

        $segments = $path->getSegments();
        $finalAttribute = array_pop($segments);
        $lastRelationship = array_pop($segments);

        $relationship = $this->model->getRelationship($lastRelationship);
        if (! $relationship) {
            throw new \RuntimeException("Relationship {$lastRelationship} not found in model {$this->model->getName()}");
        }

        $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

        $selectOp = [
            'type' => 'select',
            'tableName' => $relatedModel->getConfig()->getTable(),
            'attributes' => ['id'],
            'filters' => [
                'op' => 'and',
                'conditions' => [[
                    'attribute' => implode('.', $segments).($segments ? '.' : '').$finalAttribute,
                    'op' => $condition['op'],
                    'value' => $condition['value'],
                ]],
            ],
        ];

        $result = $this->dbOps->add($selectOp)->execute();
        $resolvedIds = array_column($result['data'], 'id');

        // Different conditions based on relationship type
        if ($relationship instanceof BelongsTo) {
            return [[
                'op' => 'in',
                'attribute' => $relationship->getForeignKey(),
                'value' => $resolvedIds,
            ]];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return [[
                'op' => 'in',
                'attribute' => $relationship->getLocalKey(),
                'value' => $resolvedIds,
            ]];
        }

        throw new \RuntimeException("Unsupported relationship type for {$lastRelationship}");
    }
}
