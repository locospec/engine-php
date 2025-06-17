<?php

namespace LCSEngine\Registry;

/**
 * MutatorRegistry manages the registration of mutators.
 *
 * This class extends AbstractRegistry to provide specific functionality for managing
 * mutator It maintains a graph representation
 * of model relationships that can be used for analysis and traversal.
 */
class MutatorRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier.
     *
     * @return string Returns 'mutator' as the registry type
     */
    public function getType(): string
    {
        return 'mutator';
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
}
