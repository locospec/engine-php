<?php

namespace Locospec\LCS\Registry;

use Locospec\LCS\Database\Operations\DatabaseOperationInterface;

interface DatabaseDriverInterface
{
    /**
     * Get the name identifier for a registry item.
     *
     * @param  mixed  $item  The item to get the name from
     * @return string The name of the item
     */
    public function getName(): string;

    /**
     * Run a collection of database operations
     *
     * @param  DatabaseOperationInterface[]  $operations  Array of operations to execute
     * @return array Results of operation execution
     */
    public function run(array $operations): array;
}
