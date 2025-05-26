<?php

namespace LCSEngine\Tasks;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\DatabaseDriverInterface;
use LCSEngine\Registry\TaskRegistry;

class TaskFactory
{
    private TaskRegistry $taskRegistry;

    private ?DatabaseDriverInterface $databaseOperator = null;

    /**
     * Initialize the task factory with task registry
     */
    public function __construct(TaskRegistry $taskRegistry)
    {
        $this->taskRegistry = $taskRegistry;
    }

    /**
     * Register database operator for database tasks
     */
    public function registerDatabaseOperator(DatabaseDriverInterface $operator): void
    {
        $this->databaseOperator = $operator;
    }

    /**
     * Create a task instance by name
     *
     * @throws InvalidArgumentException If task not found or invalid
     */
    public function createTask(string $name): TaskInterface
    {
        // Get task class from registry
        $className = $this->taskRegistry->get($name);

        if (! $className) {
            throw new InvalidArgumentException("Task not found: {$name}");
        }

        if (! class_exists($className)) {
            throw new InvalidArgumentException("Task class does not exist: {$className}");
        }

        // Create task instance
        $task = new $className;

        if (! $task instanceof TaskInterface) {
            throw new InvalidArgumentException("Class {$className} must implement TaskInterface");
        }

        // Inject database operator for database tasks if available
        if ($this->databaseOperator === null) {
            throw new InvalidArgumentException(
                "Cannot create database task '{$name}': No database operator registered. ".
                    'Call TaskFactory::registerDatabaseOperator first.'
            );
        }
        $task->setDatabaseOperator($this->databaseOperator);

        return $task;
    }

    /**
     * Check if a database operator is registered
     */
    public function hasDatabaseOperator(): bool
    {
        return $this->databaseOperator !== null;
    }
}
