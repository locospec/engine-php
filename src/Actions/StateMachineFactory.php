<?php

namespace Locospec\Engine\Actions;

use Locospec\Engine\LCS;
use Locospec\Engine\Registry\TaskRegistry;
use Locospec\Engine\StateMachine\ContextInterface;
use Locospec\Engine\StateMachine\StateMachine;

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
