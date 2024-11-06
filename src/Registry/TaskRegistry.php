<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Tasks\TaskInterface;

class TaskRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier
     */
    public function getType(): string
    {
        return 'task';
    }

    /**
     * Register a task implementation
     *
     * @param string $name Task identifier
     * @param string $taskClass Fully qualified class name of the task
     * @throws InvalidArgumentException
     */
    public function registerTask(string $name, string $taskClass): void
    {
        if (!class_exists($taskClass)) {
            throw new InvalidArgumentException("Task class does not exist: {$taskClass}");
        }

        if (!is_subclass_of($taskClass, TaskInterface::class)) {
            throw new InvalidArgumentException(
                "Task class must implement TaskInterface: {$taskClass}"
            );
        }

        $this->register([
            'name' => $name,
            'class' => $taskClass
        ]);
    }

    /**
     * Get a task instance by name
     *
     * @param string $name Task identifier
     * @return TaskInterface
     * @throws InvalidArgumentException
     */
    public function getTask(string $name): TaskInterface
    {
        $task = $this->get($name);
        if (!$task) {
            throw new InvalidArgumentException("Task not found: {$name}");
        }

        $className = $task['class'];
        return new $className();
    }

    /**
     * Get the name for an item in the registry
     *
     * @param mixed $item The item to get the name from
     * @return string The name of the item
     */
    protected function getItemName(mixed $item): string
    {
        return $item['name'];
    }
}
