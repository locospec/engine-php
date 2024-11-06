<?php

namespace Locospec\EnginePhp\Tasks;

use Locospec\EnginePhp\StateMachine\ContextInterface;

interface TaskInterface
{
    public function getName(): string;

    /**
     * Set the execution context
     */
    public function setContext(ContextInterface $context): void;

    /**
     * Execute the task with the given input
     *
     * @param array $input The input data for the task
     * @return array The result of the task execution
     */
    public function execute(array $input): array;
}
