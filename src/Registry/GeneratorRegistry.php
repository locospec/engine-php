<?php

namespace LCSEngine\Registry;

class GeneratorRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier
     */
    public function getType(): string
    {
        return 'generator';
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

    public function register(mixed $generator): void
    {
        $name = $generator['name'];
        $className = $generator['className'];

        $this->items[$name] = $className;

        if ($this->defaultGenerator === null) {
            $this->defaultGenerator = $name;
        }
    }
}
