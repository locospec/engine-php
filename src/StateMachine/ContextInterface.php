<?php

namespace Locospec\Engine\StateMachine;

interface ContextInterface
{
    /**
     * Set a value in the context
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get a value from the context
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists in the context
     */
    public function has(string $key): bool;

    /**
     * Get all context data
     */
    public function all(): array;
}
