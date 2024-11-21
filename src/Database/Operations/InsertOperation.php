<?php

namespace Locospec\LCS\Database\Operations;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class InsertOperation extends AbstractDatabaseOperation
{
    /**
     * Data to be inserted
     * For single insert: ['column' => 'value']
     * For bulk insert: [['column' => 'value'], ['column' => 'value']]
     */
    private array $data;

    /**
     * Create a new insert operation
     */
    public function __construct(string $table, array $data)
    {
        $this->data = $data;
        parent::__construct($table);
    }

    /**
     * Get the type identifier for this operation
     */
    public function getType(): string
    {
        return 'insert';
    }

    /**
     * Get the data to be inserted
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Check if this is a bulk insert operation
     */
    public function isBulkInsert(): bool
    {
        return isset($this->data[0]) && is_array($this->data[0]);
    }

    /**
     * Create a single row insert operation
     */
    public static function single(string $table, array $values): self
    {
        return new self($table, $values);
    }

    /**
     * Create a bulk insert operation
     *
     * @param  string  $table  Table name
     * @param  array[]  $rows  Array of rows, each row being an associative array
     */
    public static function bulk(string $table, array $rows): self
    {
        return new self($table, $rows);
    }

    /**
     * Validate the insert operation
     */
    protected function validateOperation(): void
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException('Insert operation requires data');
        }

        if ($this->isBulkInsert()) {
            $this->validateBulkInsert();
        } else {
            $this->validateSingleInsert();
        }
    }

    /**
     * Validate bulk insert data structure
     */
    private function validateBulkInsert(): void
    {
        if (! is_array($this->data[0])) {
            throw new InvalidArgumentException(
                'Bulk insert requires an array of rows'
            );
        }

        // Get the columns from first row
        $columns = array_keys($this->data[0]);

        // Validate all rows have the same columns
        foreach ($this->data as $index => $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException(
                    "Invalid row at index {$index}: must be an array"
                );
            }

            $rowColumns = array_keys($row);
            if ($rowColumns !== $columns) {
                throw new InvalidArgumentException(
                    "Row at index {$index} has different columns than the first row"
                );
            }

            // Validate column names
            foreach ($rowColumns as $column) {
                if (! is_string($column)) {
                    throw new InvalidArgumentException(
                        "Invalid column name in row {$index}: column names must be strings"
                    );
                }
            }
        }
    }

    /**
     * Validate single insert data structure
     */
    private function validateSingleInsert(): void
    {
        if (! is_array($this->data)) {
            throw new InvalidArgumentException('Insert data must be an array');
        }

        foreach ($this->data as $column => $value) {
            if (! is_string($column)) {
                throw new InvalidArgumentException(
                    'Invalid column name: column names must be strings'
                );
            }
        }
    }

    /**
     * Get the columns for this insert operation
     */
    public function getColumns(): array
    {
        if ($this->isBulkInsert()) {
            return array_keys($this->data[0]);
        }

        return array_keys($this->data);
    }

    /**
     * Convert operation to array format
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'data' => $this->data,
            'is_bulk' => $this->isBulkInsert(),
            'columns' => $this->getColumns(),
        ]);
    }
}
