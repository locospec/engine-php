<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Database\QueryContext;
use LCSEngine\StateMachine\ContextInterface;

class FindEntityTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'find_entity';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        $queryPayload = $taskArgs;
        $queryPayload['type'] = 'select';
        $context = $input['payload'];

        // Initialize DB Operator Collection
        $dbOps = new DatabaseOperationsCollection($this->operator);

        if (! empty($context)) {
            $createdContext = QueryContext::create($context);
            $dbOps->setContext($createdContext);
        }

        // Set registry manager
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());
        $dbOps->add($queryPayload);

        $response = $dbOps->execute($this->operator);

        if (! empty($response[0]['result'])) {
            $input['result'] = $response[0]['result'][0];
        } else {
            $input['result'] = [];
        }

        return $input;
    }
}
