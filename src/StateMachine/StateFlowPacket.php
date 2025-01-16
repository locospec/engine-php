<?php

namespace Locospec\Engine\StateMachine;

class StateFlowPacket
{
    public array $currentInput = [];

    public array $currentOutput = [];

    public array $stateHistory = [];

    private string $currentStateName = '';

    public ContextInterface $context;

    public function __construct()
    {
        $this->context = new Context;  // Create context internally
    }

    public function startState(string $stateName): void
    {
        $this->currentStateName = $stateName;
        $this->stateHistory[$stateName] = [
            'stateName' => $stateName,
            'input' => $this->currentInput,
            'output' => null,
            'analytics' => [
                'startTime' => microtime(true),
                'endTime' => null,
                'duration' => null,
            ],
            'debug' => [],
        ];
    }

    public function endState(float $duration): void
    {
        if (isset($this->stateHistory[$this->currentStateName])) {
            $this->stateHistory[$this->currentStateName]['output'] = $this->currentOutput;
            $this->stateHistory[$this->currentStateName]['analytics']['endTime'] = microtime(true);
            $this->stateHistory[$this->currentStateName]['analytics']['duration'] = $duration;
        }
        $this->currentInput = $this->currentOutput;
    }

    public function addDebugLog(string $message): void
    {
        if (! empty($this->currentStateName) && isset($this->stateHistory[$this->currentStateName])) {
            $this->stateHistory[$this->currentStateName]['debug'][] = [
                'timestamp' => microtime(true),
                'message' => $message,
            ];
        }
    }
}
