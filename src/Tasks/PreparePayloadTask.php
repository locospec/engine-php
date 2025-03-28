<?php

namespace Locospec\Engine\Tasks;

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
            if (! empty($relationsShipKeys)) {
                $preparedPayload['expand'] = $relationsShipKeys;
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
}
