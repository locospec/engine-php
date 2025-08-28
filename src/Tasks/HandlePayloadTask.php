<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Database\QueryContext;
use LCSEngine\StateMachine\ContextInterface;

class HandlePayloadTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'handle_payload';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        $context = [];
        // Initialize DB Operator Collection
        $dbOps = new DatabaseOperationsCollection($this->operator);

        if (! empty($input['payload']['globalContext'])) {
            $context = $input['payload']['globalContext'];
        }

        if (! empty($input['payload']['localContext'])) {
            $context = ! empty($context) ? array_merge($context, $input['payload']['localContext']) : $input['payload']['localContext'];
        }

        if (! empty($context)) {
            $createdContext = QueryContext::create($context);
            $dbOps->setContext($createdContext);
        } else {
            $createdContext = QueryContext::create([]);
            $dbOps->setContext($createdContext);
        }

        // Set registry manager
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

        // For cascade delete we might get number of operation
        if (is_array($input['preparedPayload']) && array_is_list($input['preparedPayload'])) {
            $dbOps->addMany($input['preparedPayload']);
        } else {
            $dbOps->add($input['preparedPayload']);
        }

        $response = $dbOps->execute($this->operator);

        return [...$input, 'response' => $response];
    }
}
