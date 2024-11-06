<?php

namespace Locospec\EnginePhp\StateMachine;

class ChoiceState implements StateInterface
{
    private string $name;

    private array $choices;

    private ?string $default;

    private ?string $next = null;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->choices = $definition['Choices'];
        $this->default = $definition['Default'] ?? null;
    }

    public function execute(StateFlowPacket $packet): void
    {
        $packet->addDebugLog("Executing ChoiceState: {$this->name}");
        $this->next = $this->determineNext($packet->currentInput);

        if ($this->next === null) {
            $packet->addDebugLog("Error: Failed to determine next state in ChoiceState: {$this->name}");
            throw new \RuntimeException("Failed to determine next state in ChoiceState: {$this->name}");
        }

        $packet->addDebugLog("Chosen next state: {$this->next}");
        $packet->currentOutput = $packet->currentInput;
    }

    public function isEnd(): bool
    {
        return false;
    }

    public function getNext(): ?string
    {
        return $this->next;
    }

    public function determineNext(array $input): ?string
    {
        foreach ($this->choices as $choice) {
            if ($this->evaluateChoice($choice, $input)) {
                return $choice['Next'];
            }
        }

        return $this->default;
    }

    private function evaluateChoice(array $choice, array $input): bool
    {
        return match (true) {
            isset($choice['And']) => $this->evaluateAnd($choice['And'], $input),
            isset($choice['Or']) => $this->evaluateOr($choice['Or'], $input),
            isset($choice['Not']) => ! $this->evaluateChoice($choice['Not'], $input),
            default => $this->evaluateDataTestExpression($choice, $input)
        };
    }

    private function evaluateAnd(array $conditions, array $input): bool
    {
        foreach ($conditions as $condition) {
            if (! $this->evaluateChoice($condition, $input)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateOr(array $conditions, array $input): bool
    {
        foreach ($conditions as $condition) {
            if ($this->evaluateChoice($condition, $input)) {
                return true;
            }
        }

        return false;
    }

    private function evaluateDataTestExpression(array $expression, array $input): bool
    {
        $variable = $this->resolvePath($expression['Variable'], $input);

        if (isset($expression['BooleanEquals'])) {
            return $variable === $expression['BooleanEquals'];
        }

        if (isset($expression['IsBoolean'])) {
            return is_bool($variable);
        }

        throw new \RuntimeException("Unsupported data test expression in state: {$this->name}");
    }

    private function resolvePath(string $path, array $input): mixed
    {
        $parts = explode('.', ltrim($path, '$.'));
        $current = $input;

        foreach ($parts as $part) {
            if (! isset($current[$part])) {
                throw new \RuntimeException("Invalid path: $path in state: {$this->name}");
            }
            $current = $current[$part];
        }

        return $current;
    }
}
