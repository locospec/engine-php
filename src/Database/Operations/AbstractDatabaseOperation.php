<?php

namespace Locospec\LCS\Database\Operations;

use Locospec\LCS\Exceptions\InvalidArgumentException;

abstract class AbstractDatabaseOperation implements DatabaseOperationInterface
{
    protected string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->validate();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Validate base operation parameters
     */
    public function validate(): void
    {
        if (empty($this->table)) {
            throw new InvalidArgumentException('Table name is required for database operation');
        }

        // Additional operation-specific validation should be implemented
        // in concrete classes
        $this->validateOperation();
    }

    /**
     * Convert operation to array format
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'table' => $this->table
        ];
    }

    /**
     * Operation-specific validation
     *
     * @throws InvalidArgumentException
     */
    abstract protected function validateOperation(): void;
}
