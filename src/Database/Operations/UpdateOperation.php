<?php

namespace Locospec\LCS\Database\Operations;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Query\FilterGroup;

class UpdateOperation extends AbstractDatabaseOperation
{
    /**
     * Data to update - column/value pairs
     * Example: ['status' => 'active', 'updated_at' => '2024-03-21']
     */
    private array $data;

    /**
     * FilterGroup defining which rows to update
     */
    private FilterGroup $conditions;

    public function __construct(string $table, array $data, FilterGroup $conditions)
    {
        $this->data = $data;
        $this->conditions = $conditions;
        parent::__construct($table);
    }

    public function getType(): string
    {
        return 'update';
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getConditions(): FilterGroup
    {
        return $this->conditions;
    }

    protected function validateOperation(): void
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException('Update operation requires data to update');
        }

        // Validate column names in data
        foreach ($this->data as $column => $value) {
            if (!is_string($column)) {
                throw new InvalidArgumentException('Column names must be strings');
            }
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'data' => $this->data,
            'conditions' => $this->conditions->toArray()
        ]);
    }
}
