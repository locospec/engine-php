<?php

namespace Locospec\LCS\Database\Operations;

interface DatabaseOperationInterface
{
    /**
     * Get the type of database operation (insert, update, delete, select)
     */
    public function getType(): string;

    /**
     * Get the target table name
     */
    public function getTable(): string;

    /**
     * Convert operation to array format for validation and execution
     */
    public function toArray(): array;
}
