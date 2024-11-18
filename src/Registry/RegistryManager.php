<?php

namespace Locospec\LCS\Registry;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Tasks\AuthorizeTask;
use Locospec\LCS\Tasks\DatabaseCountTask;
use Locospec\LCS\Tasks\DatabaseDeleteTask;
use Locospec\LCS\Tasks\DatabaseInsertTask;
use Locospec\LCS\Tasks\DatabaseOperationTask;
use Locospec\LCS\Tasks\DatabasePaginateTask;
use Locospec\LCS\Tasks\DatabaseSelectTask;
use Locospec\LCS\Tasks\DatabaseUpdateTask;
use Locospec\LCS\Tasks\InsertDBTask;
use Locospec\LCS\Tasks\JSONTransformationTask;
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
        $this->register('task', JSONTransformationTask::class);

        $this->registerDatabaseTasks();
    }

    /**
     * Register all database operation related tasks
     */
    private function registerDatabaseTasks(): void
    {
        $databaseTasks = [
            DatabaseOperationTask::class,
            DatabaseInsertTask::class,
            DatabaseUpdateTask::class,
            DatabaseDeleteTask::class,
            DatabaseSelectTask::class,
            DatabaseCountTask::class,
            DatabasePaginateTask::class,
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
