<?php

namespace LCSEngine\StateMachine;

class StateFactory
{
    public static function createState(string $name, array $definition, StateMachine $stateMachine): StateInterface
    {
        return match ($definition['Type']) {
            'Task' => new TaskState($name, $definition, $stateMachine),
            'Choice' => new ChoiceState($name, $definition),
            default => throw new \RuntimeException("Unsupported state type: {$definition['Type']}")
        };
    }
}
