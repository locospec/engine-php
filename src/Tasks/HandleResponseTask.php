<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\StateMachine\ContextInterface;

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
        switch ($this->context->get('action')) {
            case '_create':
                return $this->handleCreateResponse($input);
                break;
           
            case '_read':
                return $this->handleReadResponse($input);
                break;

            case '_read_relation_options':
                return $this->handleReadOptionsResponse($input);
                break;

            default:
                break;
        }
    }

    public function handleCreateResponse(array $input): array
    {
        return [
            'data' => $input['response'][0]['result'][0],
            'meta' => $input['response'][0]['pagination'] ?? [],
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
