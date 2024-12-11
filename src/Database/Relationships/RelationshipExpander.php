<?php

namespace Locospec\LCS\Database\Relationships;

use Locospec\LCS\Database\DatabaseOperationsCollection;
use Locospec\LCS\Models\ModelDefinition;
use Locospec\LCS\Models\Relationships\BelongsTo;
use Locospec\LCS\Models\Relationships\HasMany;
use Locospec\LCS\Models\Relationships\HasOne;
use Locospec\LCS\Models\Relationships\Relationship;
use Locospec\LCS\Registry\RegistryManager;

class RelationshipExpander
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

    public function expand($dbOpResult)
    {
        $expand = $dbOpResult['operation']['expand'];
        $results = $dbOpResult['result'];

        foreach ($expand as $path) {
            $results = $this->expandSingle($path, $results);
        }

        $dbOpResult['result'] = $results;
        return $dbOpResult;
    }

    private function expandSingle($expandPath, $results)
    {
        // Split path into current relationship and remaining path
        $parts = explode('.', $expandPath, 2);
        $relationshipName = $parts[0];
        $remainingPath = $parts[1] ?? null;

        $relationship = $this->model->getRelationship($relationshipName);
        $targetModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

        $extractAndPointAttributes = $this->getExtractAndPointAttributes($relationship);

        $relation = [
            'source_model_name' => $this->model->getName(),
            'target_model_name' => $relationshipName,
            'source_model' => $this->model,
            'target_model' => $targetModel,
            'relationship' => $relationship,
            'extract_by_attribute' => $extractAndPointAttributes['extract'],
            'source_by_attribute' => $extractAndPointAttributes['point'],
            'op' => $extractAndPointAttributes['op'],
        ];

        return $this->expandRelation($results, $relation, $remainingPath);
    }

    private function expandRelation(array $results, array $relation, ?string $remainingPath): array
    {
        // Collect IDs from source records
        $sourceIds = array_filter(array_column($results, $relation['source_by_attribute']));
        if (empty($sourceIds)) {
            return $results;
        }

        // Build query to fetch related records
        $operation = [
            'type' => 'select',
            'modelName' => $relation['target_model']->getName(),
            'filters' => [
                'op' => 'and',
                'conditions' => [[
                    'attribute' => $relation['extract_by_attribute'],
                    'op' => $relation['op'],
                    'value' => array_values(array_unique($sourceIds))
                ]]
            ]
        ];

        // Add remaining expansion path if exists
        if ($remainingPath !== null) {
            $operation['expand'] = [$remainingPath];
        }

        $relatedResults = $this->dbOps->add($operation)->execute();

        $relatedRecords = $relatedResults[0]['result'] ?? [];

        return $this->mapRelatedRecords($results, $relatedRecords, $relation);
    }

    private function mapRelatedRecords(array $results, array $relatedRecords, array $relation): array
    {
        $relationship = $relation['relationship'];

        foreach ($results as &$result) {
            $sourceValue = $result[$relation['source_by_attribute']];

            $related = array_filter($relatedRecords, function ($record) use ($relation, $sourceValue) {
                return $record[$relation['extract_by_attribute']] === $sourceValue;
            });

            if ($relationship instanceof HasMany) {
                $result[$relation['target_model_name']] = array_values($related);
            } else {
                $result[$relation['target_model_name']] = !empty($related) ? reset($related) : null;
            }
        }

        return $results;
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        if ($relationship instanceof BelongsTo) {
            return [
                'extract' => $relationship->getOwnerKey(),
                'point' => $relationship->getForeignKey(),
                'op' => 'in'
            ];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return [
                'extract' => $relationship->getForeignKey(),
                'point' => $relationship->getLocalKey(),
                'op' => 'in'
            ];
        }
    }
}
