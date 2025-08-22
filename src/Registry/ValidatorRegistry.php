<?php

namespace LCSEngine\Registry;

class ValidatorRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier
     */
    public function getType(): string
    {
        return 'validator';
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

    public function register(mixed $validator): void
    {
        $name = $validator['name'];
        $className = $validator['className'];

        $this->items[$name] = $className;

        if ($this->defaultValidator === null) {
            $this->defaultValidator = $name;
        }
    }
}
