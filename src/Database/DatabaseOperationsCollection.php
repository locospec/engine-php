<?php

namespace Locospec\LCS\Database;

use Locospec\LCS\Database\Validators\DatabaseOperationsValidator;
use Locospec\LCS\Registry\DatabaseDriverInterface;
use RuntimeException;

class DatabaseOperationsCollection
{
    /** @var array[] */
    private array $operations = [];

    private DatabaseOperationsValidator $validator;

    public function __construct()
    {
        $this->validator = new DatabaseOperationsValidator;
    }

    /**
     * Add a new operation to the collection
     *
     * @param  array  $operation  The operation to add
     *
     * @throws RuntimeException if operation is invalid
     */
    public function add(array $operation): self
    {
        // Convert shorthand filters to full-form structure
        $operation = $this->convertShorthandFilters($operation);

        $validation = $this->validator->validateOperation($operation);

        if (! $validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: ' . json_encode($validation['errors'])
            );
        }

        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Add multiple operations to the collection
     *
     * @param  array[]  $operations  Array of operations to add
     *
     * @throws RuntimeException if any operation is invalid
     */
    public function addMany(array $operations): self
    {
        foreach ($operations as $operation) {
            $this->add($operation);
        }

        return $this;
    }

    /**
     * Execute all operations in collection using provided database operator
     *
     * @param  DatabaseDriverInterface  $operator  Database operator to execute operations
     * @return array Operation results from database operator
     */
    public function execute(DatabaseDriverInterface $operator): array
    {
        return $operator->run($this->operations);
    }

    /**
     * Reset the collection
     */
    public function reset(): self
    {
        $this->operations = [];

        return $this;
    }

    /**
     * Convert shorthand filters to full-form structure
     *
     * @param  array  $operation  The operation to convert
     * @return array The operation with full-form filters
     */
    private function convertShorthandFilters(array $operation): array
    {
        if (isset($operation['filters']) && is_array($operation['filters'])) {
            $operation['filters'] = $this->convertShorthandFilterConditions($operation['filters']);
        }

        return $operation;
    }

    /**
     * Convert shorthand filter conditions to full-form structure
     *
     * @param  array  $shorthandFilters  The shorthand filters to convert
     * @return array The full-form filter conditions
     */
    private function convertShorthandFilterConditions(array $shorthandFilters): array
    {
        $fullFormFilters = [];

        foreach ($shorthandFilters as $attribute => $value) {
            $fullFormFilters[] = [
                'attribute' => $attribute,
                'operator' => '=',
                'value' => $value,
            ];
        }

        return $fullFormFilters;
    }
}
