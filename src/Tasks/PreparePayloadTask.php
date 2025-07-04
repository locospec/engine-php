<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\StateMachine\ContextInterface;

class PreparePayloadTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'prepare_payload';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $payload, array $taskArgs = []): array
    {
        $preparedPayload = [];
        if (isset($payload['globalContext']['userPermissions'])) {
            $payload['locospecPermissions']['userPermissions'] = $payload['globalContext']['userPermissions'];
            unset($payload['globalContext']['userPermissions']);
        }

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
                $preparedPayload = $this->preparePayloadForRead($payload);
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

    public function preparePayloadForRead(array $payload): array
    {
        $preparedPayload = [
            'type' => 'select',
            'modelName' => $this->context->get('model')->getName(),
        ];

        $hasDeleteKey = $this->context->get('model')->hasDeleteKey();

        if ($hasDeleteKey) {
            $preparedPayload['deleteColumn'] = $this->context->get('model')->getDeleteKey()->getName();
        }

        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload['pagination'] = $payload['pagination'];
        }

        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload['sorts'] = $payload['sorts'];
        } else {
            $preparedPayload['sorts'] = [[
                'attribute' => 'created_at',
                'direction' => 'DESC',
            ]];
        }

        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            $preparedPayload['filters'] = $payload['filters'];
        }

        $preparedPayload['scopes'] = $this->context->get('query')->getAllowedScopes()->toArray();

        if (isset($payload['expand']) && ! empty($payload['expand'])) {
            $preparedPayload['expand'] = $payload['expand'];
        } else {
            $relationshipKeys = $this->context->get('model')->getRelationships()->keys()->all();
            if (! empty($relationshipKeys)) {
                $preparedPayload['expand'] = $relationshipKeys;
            }
        }

        return $preparedPayload;
    }

    public function preparePayloadForReadOptions(array $payload): array
    {
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $optionsModel = $registryManager->get('model', $payload['relation']);

        $preparedPayload = [
            'type' => 'select',
            'modelName' => $optionsModel->getName(),
        ];

        $hasDeleteKey = $optionsModel->hasDeleteKey();

        if ($hasDeleteKey) {
            $preparedPayload['deleteColumn'] = $optionsModel->getDeleteKey()->getName();
        }

        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload['pagination'] = $payload['pagination'];
        }

        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload['sorts'] = $payload['sorts'];
        } else {
            $preparedPayload['sorts'] = [[
                'attribute' => 'created_at',
                'direction' => 'DESC',
            ]];
        }

        if (isset($payload['filters']) && ! empty($payload['filters'])) {
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

    public function preparePayloadForCreateAndUpdate(array $payload, string $dbOp): array
    {
        try {
            $preparedPayload = [
                'type' => $dbOp,
                'modelName' => $this->context->get('model')->getName(),
            ];

            if ($dbOp === 'update') {
                if (isset($payload['filters'])) {
                    $preparedPayload['filters'] = $payload['filters'];
                } else {
                    $primaryKey = $this->context->get('model')->getPrimaryKey()->getName();
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
            $attributes = $this->context->get('mutator')->getAttributes()->filter(fn ($attribute) => ! $attribute->isAliasKey())->all();
            $dbOps = new DatabaseOperationsCollection($this->operator);
            $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

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

                        if (! in_array($dbOp, $generator->getOperations()->map(fn ($operation) => $operation->value)->all())) {
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
                        $generation['modelName'] = $this->context->get('model')->getName();
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
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function preparePayloadForReadOne(array $payload): array
    {
        $preparedPayload = [];
        if (isset($payload['primaryKey']) && ! empty($payload['primaryKey'])) {
            $preparedPayload = [
                'type' => 'select',
                'modelName' => $this->context->get('model')->getName(),
            ];

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

    public function preparePayloadForDelete(array $payload, string $dbOp): array
    {
        $softDelete = $this->context->get('model')->getConfig()->getSoftDelete();
        $sourceModel = $this->context->get('model');
        $primaryKey = $this->context->get('model')->getConfig()->getPrimaryKey();
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

        $cascadePayloads = $this->prepareCascadeDeletePayloads($sourceModel->getName(), [$payload['primary_key']], $dbOps);

        $preparedPayload = array_merge([$mainPayload], $cascadePayloads);

        return $preparedPayload;
    }

    private function prepareCascadeDeletePayloads(
        string $sourceModelName,
        array $sourceIds,
        $dbOps,
        array &$cascadePayloads = [],
    ): array {
        $sourceModel = $this->context->get('lcs')->getRegistryManager()->get('model', $sourceModelName);
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
