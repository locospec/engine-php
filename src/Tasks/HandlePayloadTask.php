<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Database\DatabaseOperationsCollection;
use Locospec\Engine\Database\QueryContext;
use Locospec\Engine\StateMachine\ContextInterface;

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
        if (isset($input['preparedPayload']) && ! empty($input['preparedPayload'])) {
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
            }

            // Set registry manager
            $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

            if (is_array($input['preparedPayload']) && array_is_list($input['preparedPayload'])) {
                $dbOps->addMany($input['preparedPayload']);
            } else {
                $dbOps->add($input['preparedPayload']);
            }

            $response = $dbOps->execute($this->operator);

            return [...$input, 'response' => $response];
        } else {
            return [...$input];
        }
    }
}
