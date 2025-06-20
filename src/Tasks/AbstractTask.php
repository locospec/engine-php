<?php

namespace LCSEngine\Tasks;

use Illuminate\Support\Collection;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\DatabaseDriverInterface;
use LCSEngine\StateMachine\ContextInterface;

abstract class AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    protected Collection $attributes;

    protected DatabaseDriverInterface $operator;

    protected string $action;

    /**
     * Task metadata
     */
    protected string $name = '';

    protected string $description = '';

    protected array $requiredContextKeys = ['attributes', 'action'];

    /**
     * Set the execution context
     */
    public function setContext(ContextInterface $context): void
    {
        $this->validateContext($context);
        $this->context = $context;
        $this->attributes = $context->get('attributes');
        $this->action = $context->get('action');
    }

    public function setDatabaseOperator(DatabaseDriverInterface $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * Execute the task
     */
    abstract public function execute(array $input, array $taskArgs = []): array;

    /**
     * Validate that context has required properties
     *
     * @throws InvalidArgumentException
     */
    protected function validateContext(ContextInterface $context): void
    {
        foreach ($this->requiredContextKeys as $key) {
            if (! $context->has($key)) {
                throw new InvalidArgumentException(
                    sprintf('Required context key "%s" not found for task "%s"', $key, $this->name)
                );
            }
        }
    }

    /**
     * Helper method to safely get context value
     */
    protected function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context->has($key) ? $this->context->get($key) : $default;
    }

    /**
     * Get task name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get task description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
