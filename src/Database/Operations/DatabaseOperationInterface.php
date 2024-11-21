<?php

namespace Locospec\LCS\Database\Operations;

interface DatabaseOperationInterface
{
    /**
     * Get the type of database operation
     */
    public function getType(): string;

    /**
     * Get the target table name
     */
    public function getTable(): string;

    /**
     * Convert operation to array format
     */
    public function toArray(): array;

    /**
     * Validate the operation parameters
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void;
}
