<?php

namespace Locospec\LCS\Registry;

use Locospec\LCS\Exceptions\InvalidArgumentException;

abstract class AbstractRegistry implements RegistryInterface
{
    protected array $items = [];

    protected ?string $defaultDriver = null;

    public function register(mixed $item): void
    {
        $name = $this->getItemName($item);
        if (isset($this->items[$name])) {
            throw new InvalidArgumentException("Item '{$name}' is already registered in ".$this->getType());
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

    public function getDefaultDriver()
    {
        if (! $this->has($this->defaultDriver)) {
            throw new InvalidArgumentException('Default database driver not found');
        }

        return $this->get($this->defaultDriver);
    }

    public function setDefaultDriver(string $driverName): void
    {
        if (! $this->has($driverName)) {
            throw new InvalidArgumentException("Database driver '{$driverName}' not found");
        }

        $this->defaultDriver = $driverName;
    }
}
