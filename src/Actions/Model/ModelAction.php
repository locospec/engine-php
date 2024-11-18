<?php

namespace Locospec\LCS\Actions\Model;

use Locospec\LCS\Models\ModelDefinition;
use Locospec\LCS\StateMachine\StateMachine;
use Locospec\LCS\Registry\TaskRegistry;
use Locospec\LCS\Database\DatabaseOperatorInterface;

/**
 * Base ModelAction class that handles model-specific actions
 * This is part of the core LCS package and is framework-agnostic
 */
abstract class ModelAction
{
    protected ModelDefinition $model;
    protected array $config;
    protected string $name;
    protected ?DatabaseOperatorInterface $databaseOperator;
    protected TaskRegistry $taskRegistry;
    protected ModelActionValidator $validator;

    public function __construct(
        ModelDefinition $model,
        TaskRegistry $taskRegistry,
        ?DatabaseOperatorInterface $databaseOperator = null,
        array $config = []
    ) {
        $this->model = $model;
        $this->taskRegistry = $taskRegistry;
        $this->databaseOperator = $databaseOperator;
        $this->config = $config;
        $this->name = static::getName();
        $this->validator = new ModelActionValidator();
    }

    /**
     * Get the identifier name for this action
     */
    abstract public static function getName(): string;

    /**
     * Define the state machine flow for this action
     */
    abstract protected function getStateMachineDefinition(): array;

    /**
     * Execute the action with given input
     */
    public function execute(array $input = []): array
    {
        // Validate input
        $methodName = 'validate' . ucfirst($this->name);
        $this->validator->$methodName($input, $this->model);

        // Normalize conditions if present
        $input = $this->validator->normalizeConditions($input);

        // Create state machine instance
        $stateMachine = new StateMachine($this->getStateMachineDefinition(), $this->taskRegistry);

        // Register database operator if available
        if ($this->databaseOperator) {
            $stateMachine->registerDatabaseOperator($this->databaseOperator);
        }

        // Set required context
        $stateMachine->setContext('model', $this->model);
        $stateMachine->setContext('schema', $this->model->getSchema());
        $stateMachine->setContext('action', $this->name);
        $stateMachine->setContext('config', $this->config);

        // Execute state machine
        $packet = $stateMachine->execute($input);

        return $packet->currentOutput;
    }

    /**
     * Get the model definition this action operates on
     */
    public function getModel(): ModelDefinition
    {
        return $this->model;
    }

    /**
     * Get the action configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
