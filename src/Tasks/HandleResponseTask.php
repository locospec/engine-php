<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\StateMachine\ContextInterface;
use Locospec\Engine\LCS;


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

    public function execute(array $input): array
    {
        $res = [];
        $logger = LCS::getLogger();
        
        switch ($this->context->get('action')) {
            case '_read':
                $res = $this->handleReadResponse($input);
                break;

            case '_read_relation_options':
                $res = $this->handleReadOptionsResponse($input);
                break;

            default:
                break;
        }

        if($logger->isQueryLogsEnabled()) {
            $res['meta']['logs'] = $logger->getLogs('dbOps');
        }

        return $res;
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
        $modifiedResult = array_map(function ($item) {
            return [
                'const' => $item['const'],
                'title' => $item['title'],
            ];
        }, $input['response'][0]['result']);

        return [
            'data' => $modifiedResult,
            'meta' => $input['response'][0]['pagination'] ?? [],
        ];
    }
}
