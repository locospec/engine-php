<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\PayloadBuilders\ReadPayloadBuilder;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

/**
 * Prepares the payload for database operations based on the current action in the state machine.
 * This task transforms the incoming payload into a structured format that the database operator can execute.
 */
class PreparePayloadTask extends AbstractTask implements TaskInterface
{
    use PayloadPreparationHelpers;
    protected ContextInterface $context;

    /**
     * Gets the name of the task.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'prepare_payload';
    }

    /**
     * Sets the context for the task.
     *
     * @param ContextInterface $context The state machine context.
     */
    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    /**
     * Executes the task, dispatching to the appropriate payload preparation method based on the action.
     *
     * @param array $payload The incoming payload.
     * @param array $taskArgs Additional arguments for the task.
     * @return array The original payload and the prepared payload.
     */
    public function execute(array $payload, array $taskArgs = []): array
    {
        $preparedPayload = [];
        // Move user permissions from global context to a dedicated key for locospec.
        if (isset($payload['globalContext']['userPermissions'])) {
            $payload['locospecPermissions']['userPermissions'] = $payload['globalContext']['userPermissions'];
            unset($payload['globalContext']['userPermissions']);
        }

        // Determine the appropriate payload preparation method based on the action from the context.
        switch ($this->context->get('action')) {
            case '_create':
                $preparedPayload = $this->preparePayloadForCreateAndUpdate($payload, 'insert');
                break;

            case '_update':
                $preparedPayload = $this->preparePayloadForCreateAndUpdate($payload, 'update');
                break;

            case '_delete':
                $preparedPayload = $this->preparePayloadForDelete($payload, 'delete');
                break;

            case '_read':
                $readPayloadBuilder = new ReadPayloadBuilder($this->context);
                $preparedPayload = $readPayloadBuilder->build($payload)->toArray();
                // Do not remove the following yet
                // $preparedPayload = $this->preparePayloadForRead($payload);
                break;

            case '_read_one':
                $preparedPayload = $this->preparePayloadForReadOne($payload);
                break;

            case '_config':
                $preparedPayload = $this->preparePayloadForReadOne($payload);
                break;

            case '_read_relation_options':
                $preparedPayload = $this->preparePayloadForReadOptions($payload);
                break;

            default:
                break;
        }

        return [
            'payload' => $payload,
            'preparedPayload' => $preparedPayload,
        ];
    }

    /**
     * Prepares the payload for a read (select) operation.
     *
     * @param array $payload The incoming payload.
     * @return array The prepared payload for the read operation.
     */
    public function preparePayloadForRead(array $payload): array
    {

        $model = $this->context->get('model');

        $preparedPayload = [
            'type' => 'select',
            'modelName' => $model->getName(),
        ];

        // Check for a soft delete key and add it to the payload if it exists.
        $hasDeleteKey = $model->hasDeleteKey();

        if ($hasDeleteKey) {
            $preparedPayload['deleteColumn'] = $model->getDeleteKey()->getName();
        }

        $this->preparePagination($payload, $preparedPayload);

        $primaryKeyAttributeKey = $model->getPrimaryKey()->getName();

        $this->prepareSorts($payload, $preparedPayload, $primaryKeyAttributeKey);

        // Transfer filters from the incoming payload to the prepared payload.
        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            $preparedPayload['filters'] = $payload['filters'];
        }

        // Set the allowed scopes for the query.
        $preparedPayload['scopes'] = $this->context->get('query')->getAllowedScopes()->toArray();

        // Handle relationship expansion, using payload's expand if present, otherwise default from query.
        if (isset($payload['expand']) && ! empty($payload['expand'])) {
            $preparedPayload['expand'] = $payload['expand'];
        } else {
            $preparedPayload['expand'] = $this->context->get('query')->getExpand()->toArray();
            // $relationshipKeys = $model->getRelationships()->keys()->all();
            // if (! empty($relationshipKeys)) {
            //     $preparedPayload['expand'] = $relationshipKeys;
            // }
        }

        return $preparedPayload;
    }

    /**
     * Prepares the payload for reading relationship options, typically for UI elements like dropdowns.
     *
     * @param array $payload The incoming payload containing relation information.
     * @return array The prepared payload for reading options.
     */
    public function preparePayloadForReadOptions(array $payload): array
    {
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $optionsModel = $registryManager->get('model', $payload['relation']);

        $preparedPayload = [
            'type' => 'select',
            'modelName' => $optionsModel->getName(),
        ];

        // Check for a soft delete key on the options model.
        $hasDeleteKey = $optionsModel->hasDeleteKey();

        if ($hasDeleteKey) {
            $preparedPayload['deleteColumn'] = $optionsModel->getDeleteKey()->getName();
        }

        $this->preparePagination($payload, $preparedPayload);

        $primaryKeyAttributeKey = $optionsModel->getPrimaryKey()->getName();
        $this->prepareSorts($payload, $preparedPayload, $primaryKeyAttributeKey);

        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            // Process filters, adjusting attribute paths to be relative to the relationship.
            // Read options is called when we want to filter a model by it's relationships. And generate dropdown options.
            if (isset($payload['filters']['conditions'])) {
                foreach ($payload['filters']['conditions'] as &$condition) {
                    if (isset($condition['attribute'])) {
                        $attributePath = explode('.', $condition['attribute']);
                        // Get all relationships from the model
                        $relationships = $optionsModel->getRelationships()->keys()->all();

                        // Check if any relationship exists in the path
                        foreach ($relationships as $relationship) {
                            $relationshipIndex = array_search($relationship, $attributePath);
                            if ($relationshipIndex !== false) {
                                // Get the part of the path after the relationship
                                $attributePath = array_slice($attributePath, $relationshipIndex);
                                $condition['attribute'] = implode('.', $attributePath);
                                break;
                            }
                        }
                    }
                }
            }

            $preparedPayload['filters'] = $payload['filters'];
        }

        // Process scopes, ensuring they are valid within the global context.
        if (! empty($payload['scopes']) && ! empty($payload['globalContext'])) {
            $scopes = [];
            foreach ($payload['scopes'] as $scope) {
                if (isset($payload['globalContext'][$scope])) {
                    $scopes[] = $scope;
                }
            }
            $preparedPayload['scopes'] = $scopes;
        }

        return $preparedPayload;
    }

    /**
     * Prepares the payload for create (insert) and update operations.
     *
     * @param array $payload The incoming payload.
     * @param string $dbOp The database operation type ('insert' or 'update').
     * @return array The prepared payload for the create/update operation.
     */
    public function preparePayloadForCreateAndUpdate(array $payload, string $dbOp): array
    {
        $model = $this->context->get('model');

        $preparedPayload = [
            'type' => $dbOp,
            'modelName' => $model->getName(),
        ];

        // For update operations, set up filters to target the correct record.
        // If filters are not provided, create a filter based on the primary key.
        if ($dbOp === 'update') {
            if (isset($payload['filters'])) {
                $preparedPayload['filters'] = $payload['filters'];
            } else {
                $primaryKey = $model->getPrimaryKey()->getName();
                $preparedPayload['filters'] = [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $primaryKey,
                            'op' => 'is',
                            'value' => $payload[$primaryKey],
                        ],
                    ],
                ];
                $payload['data'] = $payload;
            }
        }

        $defaultGenerator = $this->context->get('generator');
        $attributes = $this->context->get('mutator')->getAttributes()->filter(fn($attribute) => ! $attribute->isAliasKey())->all();
        $dbOps = new DatabaseOperationsCollection($this->operator);
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

        // Iterate through model attributes to process data and handle generated values.
        foreach ($attributes as $attributeName => $attribute) {
            // If the attribute already exists in payload, keep it
            if ($dbOp === 'insert' && isset($payload[$attributeName])) {
                $preparedPayload['data'][0][$attributeName] = $payload[$attributeName];

                continue;
            }

            if ($dbOp === 'update' && isset($payload['data'][$attributeName])) {
                $preparedPayload['data'][$attributeName] = $payload['data'][$attributeName];

                continue;
            }

            // Check if the attribute has a generation rule
            if (! empty($attribute->getGenerators())) {
                foreach ($attribute->getGenerators()->all() as $generator) {
                    $generation = $generator->toArray();
                    $generation['payload'] = $payload;
                    // Only process the generation if the current operation is included in the operations list

                    if (! in_array($dbOp, $generator->getOperations()->map(fn($operation) => $operation->value)->all())) {
                        continue;
                    }

                    if ($generator->getSource() !== null) {
                        $sourceKey = $generator->getSource();
                        $sourceValue = null;
                        if ($dbOp === 'update') {
                            $sourceValue = $payload['data'][$sourceKey] ?? null;
                        } else {
                            $sourceValue = $payload[$sourceKey] ?? null;
                        }

                        if ($sourceValue) {
                            $generation['sourceValue'] = $sourceValue;
                        }
                    }

                    $generation['dbOps'] = $dbOps;
                    $generation['dbOperator'] = $this->operator;
                    $generation['modelName'] = $model->getName();
                    $generation['attributeName'] = $attributeName;
                    $generation['value'] = $generator->getValue();
                    $generatedValue = $defaultGenerator->generate(
                        $generator->getType()->value,
                        $generation
                    );

                    if ($generatedValue !== null) {
                        if ($dbOp === 'update') {
                            $preparedPayload['data'][$attributeName] = $generatedValue;
                        } else {
                            $preparedPayload['data'][0][$attributeName] = $generatedValue;
                        }
                    }
                }
            }
        }

        return $preparedPayload;
    }

    /**
     * Prepares the payload for reading a single record by its primary key.
     *
     * @param array $payload The incoming payload containing the primary key.
     * @return array The prepared payload for the read_one operation.
     */
    public function preparePayloadForReadOne(array $payload): array
    {
        $preparedPayload = [];
        // Ensure a primary key is provided before preparing the payload.
        if (isset($payload['primaryKey']) && ! empty($payload['primaryKey'])) {
            $preparedPayload = [
                'type' => 'select',
                'modelName' => $this->context->get('model')->getName(),
            ];

            // Handle relationship expansion, using payload's expand if present, otherwise expand all relationships.
            if (isset($payload['expand']) && ! empty($payload['expand'])) {
                $preparedPayload['expand'] = $payload['expand'];
            } else {
                $relationshipKeys = $this->context->get('model')->getRelationships()->keys()->all();
                if (! empty($relationshipKeys)) {
                    $preparedPayload['expand'] = $relationshipKeys;
                }
            }

            $preparedPayload['filters'] = [
                'op' => 'and',
                'conditions' => [
                    [
                        'attribute' => $this->context->get('model')->getPrimaryKey()->getName(),
                        'op' => 'is',
                        'value' => $payload['primaryKey'],
                    ],
                ],
            ];
        }

        return $preparedPayload;
    }

    /**
     * Prepares the payload for a delete operation, including handling cascade deletes.
     *
     * @param array $payload The incoming payload containing the primary key of the record to delete.
     * @param string $dbOp The database operation type ('delete').
     * @return array An array of prepared payloads, including the main delete and any cascade deletes.
     */
    public function preparePayloadForDelete(array $payload, string $dbOp): array
    {
        $softDelete = $this->context->get('model')->getConfig()->getSoftDelete();
        $sourceModel = $this->context->get('model');
        $primaryKey = $this->context->get('model')->getPrimaryKey()->getName();
        $dbOps = new DatabaseOperationsCollection($this->operator);
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

        $mainPayload = [
            'type' => $dbOp,
            'modelName' => $this->context->get('model')->getName(),
            'softDelete' => $softDelete,
            'filters' => [
                'op' => 'and',
                'conditions' => [
                    [
                        'attribute' => $primaryKey,
                        'op' => 'is',
                        'value' => $payload['primary_key'],
                    ],
                ],
            ],
        ];

        $hasDeleteKey = $this->context->get('model')->hasDeleteKey();

        if ($hasDeleteKey) {
            $mainPayload['deleteColumn'] = $this->context->get('model')->getDeleteKey()->getName();
        }

        // Prepare payloads for any cascade delete operations.
        $cascadePayloads = $this->prepareCascadeDeletePayloads($sourceModel->getName(), [$payload['primary_key']], $dbOps);

        $preparedPayload = array_merge([$mainPayload], $cascadePayloads);

        return $preparedPayload;
    }

    /**
     * Recursively prepares payloads for cascade deleting related records.
     *
     * @param string $sourceModelName The name of the source model.
     * @param array $sourceIds The IDs of the source records being deleted.
     * @param mixed $dbOps The database operations collection.
     * @param array $cascadePayloads An array to accumulate the cascade delete payloads.
     * @return array The accumulated cascade delete payloads.
     */
    private function prepareCascadeDeletePayloads(
        string $sourceModelName,
        array $sourceIds,
        $dbOps,
        array &$cascadePayloads = [],
    ): array {
        $sourceModel = $this->context->get('lcs')->getRegistryManager()->get('model', $sourceModelName);
        // Get all 'has_many' relationships to identify records for cascade deletion.
        $hasManyRelationships = $sourceModel->getRelationshipsByType('has_many');

        foreach ($hasManyRelationships as $relationName => $relationship) {
            $targetModelName = $relationship->getRelatedModelName();
            $targetModelForeignKey = $relationship->getForeignKey();
            $targetModelLocalKey = $relationship->getLocalKey();
            $targetModel = $this->context->get('lcs')->getRegistryManager()->get('model', $targetModelName);

            $payload = [
                'type' => 'delete',
                'modelName' => $targetModelName,
                'softDelete' => $targetModel->getConfig()->getSoftDelete(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $targetModelForeignKey,
                            'op' => 'is_any_of',
                            'value' => $sourceIds,
                        ],
                    ],
                ],
            ];

            $hasDeleteKey = $targetModel->hasDeleteKey();

            if ($hasDeleteKey) {
                $payload['deleteColumn'] = $targetModel->getDeleteKey()->getName();
            }

            $cascadePayloads[] = $payload;

            // Get the target model and check for nested relationships
            // Check for nested relationships on the target model to continue the cascade delete recursively.
            $nestedHasManyRelationships = $targetModel->getRelationshipsByType('has_many');

            // If target model has its own has_many relationships, recurse
            if (! empty($nestedHasManyRelationships)) {
                $relatedIds = $this->getRelatedModelIds($targetModelName, $targetModelForeignKey, $sourceIds, $targetModelLocalKey, $dbOps);

                if (! empty($relatedIds)) {
                    $this->prepareCascadeDeletePayloads($targetModelName, $relatedIds, $dbOps, $cascadePayloads);
                }
            }
        }

        return $cascadePayloads;
    }

    /**
     * Fetches the IDs of related models to support recursive cascade deletes.
     *
     * @param string $modelName The name of the related model.
     * @param string $foreignKey The foreign key on the related model.
     * @param array $parentIds The IDs of the parent records.
     * @param string $localKey The local key on the parent model.
     * @param mixed $dbOps The database operations collection.
     * @return array An array of related model IDs.
     */
    private function getRelatedModelIds(string $modelName, string $foreignKey, array $parentIds, string $localKey, $dbOps): array
    {
        $relatedIds = [];
        $payload = [
            'type' => 'select',
            'modelName' => $modelName,
            'filters' => [
                [
                    'attribute' => $foreignKey,
                    'op' => 'is_any_of',
                    'value' => $parentIds,
                ],
            ],
        ];

        $dbOps->add($payload);
        $response = $dbOps->execute($this->operator);

        if (isset($response[0]['result']) && is_array($response[0]['result']) && ! empty($response[0]['result'])) {
            foreach ($response[0]['result'] as $record) {
                if (isset($record[$localKey])) {
                    $relatedIds[] = $record[$localKey];
                }
            }
        }

        return $relatedIds;
    }
}
