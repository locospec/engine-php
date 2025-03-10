<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\StateMachine\ContextInterface;
use Locospec\Engine\Database\DatabaseOperationsCollection;
use Locospec\Engine\Database\QueryContext;


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

    public function execute(array $input): array
    {
        // Initialize DB Operator Collection
        $dbOps = new DatabaseOperationsCollection($this->operator);

        $context = QueryContext::create([
              'search' => $input['payload']['search']
        ]);

        if(isset($input['payload']['search']) && !empty($input['payload']['search'])){
            $dbOps->setContext($context);
        }

        // Set registry manager
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());
        $dbOps->add($input['preparedPayload']);
        $response = $dbOps->execute($this->operator);
        return [ ...$input,'response' => $response ];
    }
}
