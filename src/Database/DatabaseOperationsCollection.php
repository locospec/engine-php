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
        // Convert shorthand filters to full-form structure if present
        // if (isset($operation['filters']) && is_array($operation['filters'])) {
        //     $operation = $this->convertShorthandFilters($operation);
        // }

        if (isset($operation['filters'])) {
            $operation = $this->convertShorthandFilters($operation);
        }

        $validation = $this->validator->validateOperation($operation);

        if (! $validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
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
        $filters = $operation['filters'];

        // If filters is not an array, return unchanged
        if (! is_array($filters)) {
            return $operation;
        }

        // If already in full form (has op and conditions), return unchanged
        if (isset($filters['op']) && isset($filters['conditions'])) {
            return $operation;
        }

        // Handle array format of conditions
        if (isset($filters[0])) {
            // It's a numeric array, each element should be a condition
            $operation['filters'] = [
                'op' => 'and',
                'conditions' => $filters,
            ];

            return $operation;
        }

        // Only process if filters exist and don't already have the proper structure
        if (isset($operation['filters']) && is_array($operation['filters'])) {
            // Check if filters are already in full form (have 'op' and 'conditions')
            if (! isset($operation['filters']['op']) && ! isset($operation['filters']['conditions'])) {
                $conditions = [];

                // Convert each shorthand filter to a full condition
                foreach ($operation['filters'] as $attribute => $value) {
                    $conditions[] = [
                        'op' => 'eq',
                        'attribute' => $attribute,
                        'value' => $value,
                    ];
                }

                // Wrap conditions in an 'and' group
                $operation['filters'] = [
                    'op' => 'and',
                    'conditions' => $conditions,
                ];
            }
        }

        return $operation;
    }
}
