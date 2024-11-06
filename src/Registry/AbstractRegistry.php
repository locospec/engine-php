<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

abstract class AbstractRegistry implements RegistryInterface
{
    protected array $items = [];

    public function register(mixed $item): void
    {
        $name = $this->getItemName($item);
        if (isset($this->items[$name])) {
            throw new InvalidArgumentException("Item '{$name}' is already registered in " . $this->getType());
        }
        $this->items[$name] = $item;
    }

    public function get(string $name): mixed
    {
        return $this->items[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->items[$name]);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function clear(): void
    {
        $this->items = [];
    }

    abstract protected function getItemName(mixed $item): string;
}