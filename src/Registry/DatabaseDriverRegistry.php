<?php

namespace LCSEngine\Registry;

use LCSEngine\Exceptions\InvalidArgumentException;

class DatabaseDriverRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier
     */
    public function getType(): string
    {
        return 'database_driver';
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

    public function register(mixed $connection): void
    {
        $name = $connection['name'];
        $className = $connection['className'];

        if ($name === trim('')) {
            throw new InvalidArgumentException("Please set Driver name using getName on {$className}");
        }

        $this->items[$name] = $className;

        if ($this->defaultDriver === null) {
            $this->defaultDriver = $name;
        }
    }
}
