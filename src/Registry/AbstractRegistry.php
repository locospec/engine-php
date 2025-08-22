<?php

namespace LCSEngine\Registry;

use LCSEngine\Exceptions\InvalidArgumentException;

abstract class AbstractRegistry implements RegistryInterface
{
    protected array $items = [];

    protected ?string $defaultDriver = null;

    protected ?string $defaultGenerator = null;

    protected ?string $defaultValidator = null;

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

    public function getDefaultGenerator()
    {
        if (! $this->has($this->defaultGenerator)) {
            throw new InvalidArgumentException('Default generator not found');
        }

        return $this->get($this->defaultGenerator);
    }

    public function setDefaultGenerator(string $generatorName): void
    {
        if (! $this->has($generatorName)) {
            throw new InvalidArgumentException("Generator '{$generatorName}' not found");
        }

        $this->defaultGenerator = $generatorName;
    }

    public function getDefaultValidator()
    {
        if (! $this->has($this->defaultValidator)) {
            throw new InvalidArgumentException('Default validator not found');
        }

        return $this->get($this->defaultValidator);
    }

    public function setDefaultValidator(string $validatorName): void
    {
        if (! $this->has($validatorName)) {
            throw new InvalidArgumentException("Validator '{$validatorName}' not found");
        }

        $this->defaultValidator = $validatorName;
    }
}
