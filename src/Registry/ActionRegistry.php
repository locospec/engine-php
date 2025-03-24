<?php

namespace Locospec\Engine\Registry;

/**
 * ActionRegistry manages the registration of actions.
 *
 * This class extends AbstractRegistry to provide specific functionality for managing
 * model definitions and their relationships. It maintains a graph representation
 * of model relationships that can be used for analysis and traversal.
 */
class ActionRegistry extends AbstractRegistry
{
    /**
     * Get the registry type identifier.
     *
     * @return string Returns 'action' as the registry type
     */
    public function getType(): string
    {
        return 'action';
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
