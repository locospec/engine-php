<?php

namespace LCSEngine\StateMachine;

class TaskState implements StateInterface
{
    private string $name;

    private string $resource;

    private ?string $next;

    private bool $end;

    private StateMachine $stateMachine;

    private ?array $taskArgs;

    public function __construct(string $name, array $definition, StateMachine $stateMachine)
    {
        $this->name = $name;
        $this->resource = $definition['Resource'];
        $this->next = $definition['Next'] ?? null;
        $this->end = $definition['End'] ?? false;
        $this->stateMachine = $stateMachine;
        $this->taskArgs = $definition['TaskArgs'] ?? [];
    }

    public function execute(StateFlowPacket $packet): void
    {
        $packet->addDebugLog("Executing TaskState: {$this->name}");
        $resource = $this->stateMachine->getTask($this->resource);

        if (! $resource instanceof \LCSEngine\Tasks\TaskInterface) {
            throw new \RuntimeException("Invalid resource type for {$this->resource}");
        }

        $resource->setContext($packet->context);
        $packet->currentOutput = $resource->execute($packet->currentInput, $this->taskArgs);
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
