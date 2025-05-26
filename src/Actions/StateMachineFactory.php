<?php

namespace LCSEngine\Actions;

use LCSEngine\LCS;
use LCSEngine\Registry\TaskRegistry;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\StateMachine\StateMachine;

class StateMachineFactory
{
    private TaskRegistry $taskRegistry;

    private LCS $lcs;

    public function __construct(LCS $lcs)
    {
        $this->lcs = $lcs;
        $this->taskRegistry = $lcs->getRegistryManager()->getRegistry('task');
    }

    public function create(array $definition, ContextInterface $context): StateMachine
    {
        // Create state machine with task registry
        $stateMachine = new StateMachine($definition, $this->taskRegistry);
        $stateMachine->registerDatabaseOperator($this->lcs->getDefaultDriverOfType('database_driver'));

        // Set context values
        foreach ($context->all() as $key => $value) {
            $stateMachine->setContext($key, $value);
        }

        return $stateMachine;
    }

    public function getTaskRegistry(): TaskRegistry
    {
        return $this->taskRegistry;
    }
}
