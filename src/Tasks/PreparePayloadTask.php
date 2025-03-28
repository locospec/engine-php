<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Database\DatabaseOperationsCollection;
use Locospec\Engine\StateMachine\ContextInterface;

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

    public function execute(array $payload): array
    {
        $preparedPayload = [];
        switch ($this->context->get('action')) {
            case '_create':
                $preparedPayload = $this->preparePayloadForCreateAndUpdate($payload, 'insert');
                break;

            case '_update':
                $preparedPayload = $this->preparePayloadForCreateAndUpdate($payload, 'update');
                break;

            case '_read':
                $preparedPayload = $this->preparePayloadForRead($payload);
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
            'viewName' => $this->context->get('view')->getName(),
        ];

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

        if (! empty($payload['globalContext'])) {
            $preparedPayload['scopes'] = array_keys($payload['globalContext']);
        }

        if (! empty($payload['localContext'])) {
            $preparedPayload['scopes'] = isset($preparedPayload['scopes']) && ! empty($preparedPayload['scopes']) ? array_merge($preparedPayload['scopes'], array_keys($payload['localContext'])) : array_keys($payload['localContext']);
        }

        if (isset($payload['expand']) && ! empty($payload['expand'])) {
            $preparedPayload['expand'] = $payload['expand'];
        } else {
            $relationshipKeys = (array) $this->context->get('model')->getRelationships();
            if (! empty($relationshipKeys)) {
                $preparedPayload['expand'] = array_keys($relationshipKeys);
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
            // 'attributes' => ["name", "uuid"]
        ];

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

        if (! empty($payload['globalContext'])) {
            $preparedPayload['scopes'] = array_keys($payload['globalContext']);
        }

        if (! empty($payload['localContext'])) {
            $preparedPayload['scopes'] = isset($preparedPayload['scopes']) && ! empty($preparedPayload['scopes']) ? array_merge($preparedPayload['scopes'], array_keys($payload['localContext'])) : array_keys($payload['localContext']);
        }

        return $preparedPayload;
    }

    public function preparePayloadForCreateAndUpdate(array $payload, string $dbOp): array
    {
        $preparedPayload = [
            'type' => $dbOp,
            'modelName' => $this->context->get('model')->getName(),
        ];

        if ($dbOp === 'update') {
            $preparedPayload['filters'] = $payload['filters'];
        }

        $generator = $this->context->get('generator');
        $attributes = $this->context->get('model')->getAttributes()->getAttributes();
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
            if (! empty($attribute->getGenerations())) {
                foreach ($attribute->getGenerations() as $generation) {
                    // Only process the generation if the current operation is included in the operations list
                    if (isset($generation->operations) && is_array($generation->operations)) {
                        if (! in_array($dbOp, $generation->operations)) {
                            continue;
                        }
                    }

                    if (isset($generation->source)) {
                        $sourceKey = $generation->source;
                        $sourceValue = null;
                        if ($dbOp === 'update') {
                            $sourceValue = $payload['data'][$sourceKey] ?? null;
                        } else {
                            $sourceValue = $payload[$sourceKey] ?? null;
                        }

                        if ($sourceValue) {
                            $generation->sourceValue = $sourceValue;
                        }
                    }

                    $generation->dbOps = $dbOps;
                    $generation->dbOperator = $this->operator;
                    $generation->modelName = $this->context->get('model')->getName();
                    $generation->attributeName = $attributeName;

                    $generatedValue = $generator->generate(
                        $generation->type,
                        (array) $generation // Convert any extra options to array
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
}
