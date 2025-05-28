<?php

namespace Locospec\Engine\StateMachine;

class Context implements ContextInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Create a new context with optional initial data
     */
    public function __construct(array $initialData = [])
    {
        foreach ($initialData as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set a value in the context
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a value from the context
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the context
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get all context data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Create a new context with the given data
     */
    public static function create(array $data = []): self
    {
        return new self($data);
    }
}
