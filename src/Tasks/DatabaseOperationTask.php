<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;

class DatabaseOperationTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.operation';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $operation = $input['operation'];
            $task = $this->createOperationTask($operation);

            // Set context for the operation task
            $task->setContext($this->context);
            $task->setDatabaseOperator($this->operator);

            // Execute the specific operation
            return $task->execute($input);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Database operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (!isset($input['operation'])) {
            throw new DatabaseOperationException('Operation type is required');
        }

        $validOperations = ['insert', 'update', 'delete', 'select', 'count', 'paginate'];
        if (!in_array($input['operation'], $validOperations)) {
            throw new DatabaseOperationException('Invalid operation type');
        }
    }

    private function createOperationTask(string $operation): AbstractDatabaseTask
    {
        return match ($operation) {
            'insert' => new DatabaseInsertTask(),
            'update' => new DatabaseUpdateTask(),
            'delete' => new DatabaseDeleteTask(),
            'select' => new DatabaseSelectTask(),
            'count' => new DatabaseCountTask(),
            'paginate' => new DatabasePaginateTask(),
            default => throw new DatabaseOperationException("Unsupported operation: {$operation}")
        };
    }
}
