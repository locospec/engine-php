<?php

namespace Locospec\LCS\StateMachine;

use Locospec\LCS\Database\DatabaseOperatorInterface;
use Locospec\LCS\Registry\TaskRegistry;
use Locospec\LCS\Tasks\TaskFactory;
use Locospec\LCS\Tasks\TaskInterface;

class StateMachine
{
    private array $states;

    private string $startAt;

    private StateFlowPacket $packet;

    private TaskRegistry $taskRegistry;

    private TaskFactory $taskFactory;

    public function __construct(array $definition, TaskRegistry $taskRegistry)
    {
        $this->taskFactory = new TaskFactory($taskRegistry);

        $this->states = [];
        foreach ($definition['States'] as $name => $stateDefinition) {
            $this->states[$name] = StateFactory::createState($name, $stateDefinition, $this);
        }
        $this->startAt = $definition['StartAt'];
        $this->packet = new StateFlowPacket;  // Create packet without context
        $this->taskRegistry = $taskRegistry;  // Create packet without context
    }

    /**
     * Register database operator for database tasks
     */
    public function registerDatabaseOperator(DatabaseOperatorInterface $operator): void
    {
        $this->taskFactory->registerDatabaseOperator($operator);
    }

    public function setContext(string $key, mixed $value): void
    {
        $this->packet->context->set($key, $value);
    }

    public function execute(array $input): StateFlowPacket
    {
        $packet = $this->packet;
        $packet->currentInput = $input;
        $packet->currentOutput = $input;
        $currentStateName = $this->startAt;

        while (true) {
            $currentState = $this->states[$currentStateName];
            $packet->startState($currentStateName);

            $packet->addDebugLog("Entering state: $currentStateName");
            $startTime = microtime(true);
            $currentState->execute($packet);
            $duration = microtime(true) - $startTime;
            $packet->endState($duration);

            if ($currentState->isEnd()) {
                $packet->addDebugLog("Reached end state: $currentStateName");
                break;
            }

            $nextStateName = $currentState->getNext();
            if ($nextStateName === null) {
                $packet->addDebugLog("Error: Next state is null for state: $currentStateName");
                throw new \RuntimeException("Next state is null for state: $currentStateName");
            }

            if (! isset($this->states[$nextStateName])) {
                $packet->addDebugLog("Error: Invalid next state: $nextStateName");
                throw new \RuntimeException("Invalid next state: $nextStateName");
            }

            $packet->addDebugLog("Transitioning from $currentStateName to $nextStateName");
            $currentStateName = $nextStateName;
        }

        return $packet;
    }

    // public function getTask(string $name): TaskInterface
    // {
    //     if (is_null($this->taskRegistry->get($name))) {
    //         throw new \RuntimeException("Task not found: $name");
    //     }
    //     $className = $this->taskRegistry->get($name);

    //     return new $className;
    // }

    public function getTask(string $name): TaskInterface
    {
        return $this->taskFactory->createTask($name);
    }

    /**
     * Get the task factory instance
     */
    public function getTaskFactory(): TaskFactory
    {
        return $this->taskFactory;
    }
}
