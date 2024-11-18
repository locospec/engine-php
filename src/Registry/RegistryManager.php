<?php

namespace Locospec\LCS\Registry;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Tasks\AuthorizeTask;
use Locospec\LCS\Tasks\InsertDBTask;
use Locospec\LCS\Tasks\ValidateTask;

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
        $this->addRegistry(new TaskRegistry);

        $this->register('task', ValidateTask::class);
        $this->register('task', AuthorizeTask::class);
        $this->register('task', InsertDBTask::class);

        $this->registerDatabaseTasks();
    }

    /**
     * Register all database operation related tasks
     */
    private function registerDatabaseTasks(): void
    {
        $databaseTasks = [
            // Main wrapper task for database operations
            // DatabaseOperationTask::class,

            // Individual operation tasks
            // InsertTask::class,
            // UpdateTask::class,
            // DeleteTask::class,
            // SelectTask::class,
            // CountTask::class,
            // PaginateTask::class
        ];

        foreach ($databaseTasks as $taskClass) {
            $this->register('task', $taskClass);
        }
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
}
