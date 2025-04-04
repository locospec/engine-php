<?php

namespace Locospec\Engine\Registry;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Tasks\AuthorizeTask;
use Locospec\Engine\Tasks\GenerateConfigTask;
use Locospec\Engine\Tasks\HandlePayloadTask;
use Locospec\Engine\Tasks\HandleResponseTask;
use Locospec\Engine\Tasks\PreparePayloadTask;
use Locospec\Engine\Tasks\ValidateTask;

class RegistryManager
{
    private array $registries = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    private function registerDefaults(): void
    {
        $this->addRegistry(new ModelRegistry);
        $this->addRegistry(new ViewRegistry);
        $this->addRegistry(new MutatorRegistry);
        $this->addRegistry(new TaskRegistry);
        $this->addRegistry(new DatabaseDriverRegistry);

        $this->register('task', ValidateTask::class);
        $this->register('task', AuthorizeTask::class);
        $this->register('task', GenerateConfigTask::class);
        $this->register('task', PreparePayloadTask::class);
        $this->register('task', HandlePayloadTask::class);
        $this->register('task', HandleResponseTask::class);
    }

    public function addRegistry(RegistryInterface $registry): void
    {
        $type = $registry->getType();
        $this->registries[$type] = $registry;
    }

    public function getRegistry(string $type): ?RegistryInterface
    {
        return $this->registries[$type] ?? null;
    }

    public function register(string $type, mixed $item): void
    {
        $registry = $this->getRegistry($type);
        if (! $registry) {
            throw new InvalidArgumentException("No registry found for type: {$type}");
        }
        $registry->register($item);
    }

    public function get(string $type, string $name): mixed
    {
        $registry = $this->getRegistry($type);

        return $registry?->get($name);
    }

    public function has(string $type, string $name): bool
    {
        $registry = $this->getRegistry($type);

        return $registry?->has($name) ?? false;
    }

    public function all(string $type): array
    {
        $registry = $this->getRegistry($type);

        return $registry?->all() ?? [];
    }

    /**
     * Get a registry item by its name.
     *
     * This method iterates through all registered registries and returns
     * the first registry item matching the given name. If no item is found,
     * it returns null.
     *
     * @return mixed|null
     */
    public function getRegisterByName(string $name): mixed
    {
        foreach ($this->registries as $registry) {
            if ($registry->has($name)) {
                return $registry->get($name);
            }
        }

        return null;
    }
}
