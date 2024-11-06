<?php

namespace Locospec\EnginePhp\StateMachine;

class TaskState implements StateInterface
{
    private string $name;

    private string $resource;

    private ?string $next;

    private bool $end;

    private StateMachine $stateMachine;

    public function __construct(string $name, array $definition, StateMachine $stateMachine)
    {
        $this->name = $name;
        $this->resource = $definition['Resource'];
        $this->next = $definition['Next'] ?? null;
        $this->end = $definition['End'] ?? false;
        $this->stateMachine = $stateMachine;
    }

    public function execute(StateFlowPacket $packet): void
    {
        $packet->addDebugLog("Executing TaskState: {$this->name}");
        $resource = $this->stateMachine->getResource($this->resource);

        if (! $resource instanceof \Locospec\EnginePhp\Tasks\TaskInterface) {
            throw new \RuntimeException("Invalid resource type for {$this->resource}");
        }

        $resource->setContext($packet->context);
        $packet->currentOutput = $resource->execute($packet->currentInput);
        $packet->addDebugLog('Resource execution completed');
    }

    public function isEnd(): bool
    {
        return $this->end;
    }

    public function getNext(): ?string
    {
        return $this->next;
    }

    public function determineNext(array $input): ?string
    {
        return $this->next;
    }
}
