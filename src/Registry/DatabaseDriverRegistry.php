<?php

namespace Locospec\LCS\Registry;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Symfony\Component\VarDumper\Cloner\Data;

class DatabaseDriverRegistry extends AbstractRegistry
{
    private ?string $defaultDriver = null;

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

    public function getDefaultDriver(): DatabaseDriverInterface
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

    public function register(mixed $className): void
    {
        $task = new $className;
        $name = $task->getName();

        if ($name === trim('')) {
            throw new InvalidArgumentException("Please set Driver name using getName on {$className}");
        }

        $this->items[$name] = $className;

        if ($this->defaultDriver === null) {
            $this->defaultDriver = $name;
        }
    }
}
