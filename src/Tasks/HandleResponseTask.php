<?php

namespace LCSEngine\Tasks;

use LCSEngine\LCS;
use LCSEngine\StateMachine\ContextInterface;

class HandleResponseTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'handle_response';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        $res = [];
        $logger = LCS::getLogger();

        switch ($this->context->get('action')) {
            case '_create':
                return $this->handleCreateResponse($input);
                break;

            case '_update':
                return $this->handleUpdateResponse($input);
                break;

            case '_delete':
                return $this->handleDeleteResponse($input);
                break;

            case '_read':
                $res = $this->handleReadResponse($input);
                break;

            case '_read_one':
                $res = $this->handleReadOneResponse($input);
                break;

            case '_read_relation_options':
                $res = $this->handleReadOptionsResponse($input);
                break;

            default:
                break;
        }

        if ($logger->isQueryLogsEnabled()) {
            $res['meta']['logs'] = $logger->getLogs('dbOps');
        }

        return $res;
    }

    public function handleCreateResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'][0],
            'meta' => [],
        ];
    }

    public function handleUpdateResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'],
            'meta' => [],
        ];
    }

    public function handleDeleteResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'],
            'meta' => [],
        ];
    }

    public function handleReadOneResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'][0],
            'meta' => [],
        ];
    }

    public function handleReadResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'],
            'meta' => $input['response'][0]['pagination'] ?? [],
        ];
    }

    public function handleReadOptionsResponse(array $input): array
    {
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $optionsModel = $registryManager->get('model', $input['payload']['relation']);
        $labelKey = $optionsModel->getLabelKey()->getName();
        $primaryKey = $optionsModel->getPrimaryKey()->getName();

        $modifiedResult = array_map(function ($item) use ($primaryKey, $labelKey) {
            return [
                'const' => $item[$primaryKey],
                'title' => $item[$labelKey],
            ];
        }, $input['response'][0]['result']);

        return [
            'data' => $modifiedResult,
            'meta' => $input['response'][0]['pagination'] ?? [],
        ];
    }
}
