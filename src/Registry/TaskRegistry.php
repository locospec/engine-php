<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

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
     * Get the name identifier for a registry item.
     *
     * @param  mixed  $item  The item to get the name from
     * @return string The name of the item
     */
    protected function getItemName(mixed $item): string
    {
        return $item->getName();
    }

    public function register(mixed $className): void
    {
        $task = new $className;
        $name = $task->getName();

        if ($name === trim('')) {
            throw new InvalidArgumentException("Please set Task name using getName on {$className}");
        }

        $this->items[$name] = $className;
    }
}
