<?php

namespace Locospec\Engine\Registry;

use Locospec\Engine\Database\Operations\DatabaseOperationInterface;

interface DatabaseDriverInterface
{
    /**
     * Run a collection of database operations
     *
     * @param  DatabaseOperationInterface[]  $operations  Array of operations to execute
     * @return array Results of operation execution
     */
    public function run(array $operations): array;
}
