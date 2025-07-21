<?php

namespace LCSEngine\Schemas\Common\Filters;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\LCS;
use LCSEngine\Logger;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Relationship;

class RelationshipExpander
{
    use RelationshipExpanderWithJoinsTrait;

    private Model $model;

    private DatabaseOperationsCollection $dbOps;

    private RegistryManager $registryManager;

    private Logger $logger;

    public function __construct(
        Model $model,
        DatabaseOperationsCollection $dbOps,
        RegistryManager $registryManager
    ) {
        $this->model = $model;
        $this->dbOps = $dbOps;
        $this->registryManager = $registryManager;
        $this->logger = LCS::getLogger();

        $this->logger?->info('RelationshipExpander initialized', ['modelName' => $model->getName()]);
    }

    public function expand($dbOpResult)
    {
        $this->logger?->info('Starting relationship expansion', [
            'modelName' => $this->model->getName(),
            'expandPaths' => $dbOpResult['operation']['expand'],
        ]);

        $expand = $dbOpResult['operation']['expand'];

        if (empty($expand)) {
            return $dbOpResult;
        }

        $results = $dbOpResult['result'];

        $this->groupExpandPaths($expand);

        foreach ($this->pathGroups as $groupId => $group) {
            $paths = $this->getPathsInGroup($groupId);

            // Generate query info (handles both single and multiple paths)
            $queryInfo = $this->generateJoinsForGroup($group);
            $tableNames = $queryInfo['tableNames'];

            // Execute the operation (handles both JOIN and non-JOIN cases)
            $operationResults = $this->executeOperation($results, $queryInfo);

            if (! empty($operationResults)) {
                $rows = $operationResults[0]['result'];

                // Sort paths by length to ensure parent paths are processed first
                usort($paths, fn ($a, $b) => strlen($a) - strlen($b));

                // Loop through all paths in the group and map results
                foreach ($paths as $path) {
                    $results = $this->mapAliasedResults($results, $rows, $path, $tableNames[$path]);
                }
            }
        }

        $dbOpResult['result'] = $results;

        $this->logger?->info('Relationship expansion completed', [
            'modelName' => $this->model->getName(),
            'expandedPaths' => $expand,
        ]);

        return $dbOpResult;
    }

    private function expandSingle($expandPath, $results)
    {
        $this->logger?->info('Expanding single relationship', ['expandPath' => $expandPath]);
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

        $this->logger?->info('Processing single relationship expansion', [
            'relationshipName' => $relationshipName,
            'remainingPath' => $remainingPath,
            'relation' => $relation,
        ]);

        return $this->expandRelation($results, $relation, $remainingPath);
    }

    private function expandRelation(array $results, array $relation, ?string $remainingPath): array
    {

        $this->logger?->info('Expanding relationship in results', [
            'sourceModel' => $relation['source_model_name'],
            'targetModel' => $relation['target_model_name'],
            'attributeMapping' => [
                'extractBy' => $relation['extract_by_attribute'],
                'pointTo' => $relation['source_by_attribute'],
                'operation' => $relation['op'],
            ],
        ]);
        // Collect IDs from source records
        $sourceIds = array_filter(array_column($results, $relation['source_by_attribute']));
        if (empty($sourceIds)) {
            $this->logger?->info('No source IDs found, skipping expansion');

            return $results;
        }

        // Build query to fetch related records
        $operation = [
            'type' => 'select',
            'purpose' => 'expand',
            'modelName' => $relation['target_model']->getName(),
            'filters' => [
                'op' => 'and',
                'conditions' => [[
                    'attribute' => $relation['extract_by_attribute'],
                    'op' => $relation['op'],
                    'value' => array_values(array_unique($sourceIds)),
                ]],
            ],
        ];

        // Add remaining expansion path if exists
        if ($remainingPath !== null) {
            $operation['expand'] = [$remainingPath];
        }

        $this->logger?->info('Fetching related records for expansion', ['operation' => $operation]);

        $relatedResults = $this->dbOps->add($operation)->execute();

        $relatedRecords = $relatedResults[0]['result'] ?? [];

        return $this->mapRelatedRecords($results, $relatedRecords, $relation);
    }

    private function mapRelatedRecords(array $results, array $relatedRecords, array $relation): array
    {
        $relationship = $relation['relationship'];

        $this->logger?->info('Mapping related records', [
            'relationship' => $relationship,
            'relatedRecordsCount' => count($relatedRecords),
            'sourceModel' => $relation['source_model_name'],
            'targetModel' => $relation['target_model_name'],
        ]);

        foreach ($results as &$result) {
            $sourceValue = $result[$relation['source_by_attribute']];
            $related = array_filter($relatedRecords, function ($record) use ($relation, $sourceValue) {
                return ($record[$relation['extract_by_attribute']] ?? null) === $sourceValue;
            });

            if ($relationship instanceof HasMany) {
                $result[$relation['target_model_name']] = array_values($related);
            } else {
                $result[$relation['target_model_name']] = ! empty($related) ? reset($related) : null;
            }
        }

        $this->logger?->info('Successfully mapped related records', [
            'sourceModel' => $relation['source_model_name'],
            'targetModel' => $relation['target_model_name'],
        ]);

        return $results;
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        $this->logger?->info('Determining extract and point attributes for relationship', [
            'relationshipType' => get_class($relationship),
        ]);

        if ($relationship instanceof BelongsTo) {
            return [
                'extract' => $relationship->getOwnerKey(),
                'point' => $relationship->getForeignKey(),
                'op' => 'is_any_of',
            ];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return [
                'extract' => $relationship->getForeignKey(),
                'point' => $relationship->getLocalKey(),
                'op' => 'is_any_of',
            ];
        }

        $this->logger?->error('Unknown relationship type encountered', ['relationship' => $relationship]);
        throw new \RuntimeException('Unknown relationship type: '.get_class($relationship));
    }
}
