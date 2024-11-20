<?php

namespace Locospec\LCS\Actions;

use Locospec\LCS\LCS;
use Locospec\LCS\Registry\TaskRegistry;
use Locospec\LCS\StateMachine\ContextInterface;
use Locospec\LCS\StateMachine\StateMachine;

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

        // Register database operator if available
        if ($this->lcs->hasDatabaseOperator()) {
            $stateMachine->registerDatabaseOperator($this->lcs->getDatabaseOperator());
        }

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
