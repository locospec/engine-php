<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Exceptions\DatabaseOperationException;
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Registry\DatabaseDriverInterface;
use Locospec\Engine\StateMachine\ContextInterface;

abstract class AbstractDatabaseTask extends AbstractTask
{
    protected ?DatabaseDriverInterface $operator = null;

    /**
     * Required context keys for database tasks
     */
    protected array $requiredContextKeys = ['schema', 'action', 'model'];

    /**
     * Set the database operator for this task
     */
    public function setDatabaseOperator(DatabaseDriverInterface $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * Get table name from model configuration
     */
    protected function getTableName(): string
    {
        $model = $this->getContextValue('model');

        return $model->getConfig()->getTable() ?? $model->getPluralName();
    }

    /**
     * Validate that the database operator is set
     *
     * @throws InvalidArgumentException
     */
    protected function validateOperator(): void
    {
        if (! $this->operator) {
            throw new InvalidArgumentException('Database operator not set for database task');
        }
    }

    /**
     * Format database operation output with standard structure
     */
    protected function formatOutput(array $result): array
    {
        if (! isset($result['result']) || ! isset($result['sql']) || ! isset($result['timing'])) {
            throw new DatabaseOperationException('Invalid operator result format');
        }

        $response = [
            'type' => 'db_task',
            'success' => true,
            'data' => $result['result'],
            'metadata' => array_merge(
                [
                    'table' => $this->getTableName(),
                    'model' => $this->getContextValue('model')->getName(),
                    'action' => $this->getContextValue('action'),
                ],
                [
                    'sql' => $result['sql'],
                    'timing' => $result['timing'],
                ]
            ),
        ];

        return $response;
    }

    /**
     * Validate context has required database operation properties
     *
     * @throws InvalidArgumentException
     */
    protected function validateContext(ContextInterface $context): void
    {
        parent::validateContext($context);

        // Validate operator is set
        $this->validateOperator();

        // Validate model exists in context
        if (! $context->has('model')) {
            throw new InvalidArgumentException('Model not found in context');
        }
    }
}
