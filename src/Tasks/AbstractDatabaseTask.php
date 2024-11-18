<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Database\DatabaseOperatorInterface;
use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\StateMachine\ContextInterface;

abstract class AbstractDatabaseTask extends AbstractTask
{
    protected ?DatabaseOperatorInterface $operator = null;

    /**
     * Required context keys for database tasks
     */
    protected array $requiredContextKeys = ['schema', 'action', 'model'];

    /**
     * Set the database operator for this task
     */
    public function setDatabaseOperator(DatabaseOperatorInterface $operator): void
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
        return [
            'success' => true,
            'data' => $result['result'] ?? null,
            'metadata' => [
                'sql' => $result['sql'] ?? null,
                'timestamp' => $result['timestamp'] ?? microtime(true),
                'table' => $this->getTableName(),
                'model' => $this->getContextValue('model')->getName(),
                'action' => $this->getContextValue('action'),
            ],
        ];
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
