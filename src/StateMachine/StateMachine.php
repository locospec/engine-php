<?php

namespace Locospec\EnginePhp\StateMachine;

use Locospec\EnginePhp\Tasks\TaskInterface;

class StateMachine
{
    private array $states;

    private string $startAt;

    private array $resourceRegistry = [];

    private StateFlowPacket $packet;

    public function __construct(array $definition)  // Remove context parameter
    {
        $this->states = [];
        foreach ($definition['States'] as $name => $stateDefinition) {
            $this->states[$name] = StateFactory::createState($name, $stateDefinition, $this);
        }
        $this->startAt = $definition['StartAt'];
        $this->packet = new StateFlowPacket;  // Create packet without context
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

    public function registerResource(string $name, string $className): void
    {
        $this->resourceRegistry[$name] = $className;
    }

    public function getResource(string $name): TaskInterface
    {
        if (! isset($this->resourceRegistry[$name])) {
            throw new \RuntimeException("Resource not found: $name");
        }
        $className = $this->resourceRegistry[$name];

        return new $className;
    }
}
