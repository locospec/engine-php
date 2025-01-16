<?php

namespace Locospec\Engine\Actions\Model;

use Locospec\Engine\Actions\StateMachineFactory;
use Locospec\Engine\LCS;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\StateMachine\Context;
use Locospec\Engine\StateMachine\StateFlowPacket;

abstract class ModelAction
{
    protected ModelDefinition $model;

    protected array $config;

    protected string $name;

    protected StateMachineFactory $stateMachineFactory;

    protected LCS $lcs;

    protected ModelActionValidator $validator;

    public function __construct(
        ModelDefinition $model,
        StateMachineFactory $stateMachineFactory,
        LCS $lcs,
        array $config = []
    ) {
        $this->model = $model;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->lcs = $lcs;
        $this->config = $config;
        $this->name = static::getName();
        $this->validator = new ModelActionValidator;
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
    public function execute(array $input = []): StateFlowPacket
    {
        // Validate input
        $methodName = 'validate'.ucfirst($this->name);
        $this->validator->$methodName($input, $this->model);

        // Normalize conditions if present
        $input = $this->validator->normalizeConditions($input);

        // Create context with required values
        $context = new Context([
            'model' => $this->model,
            'schema' => $this->model->getSchema(),
            'action' => $this->name,
            'config' => $this->config,
        ]);

        // Create state machine via factory
        $stateMachine = $this->stateMachineFactory->create(
            $this->getStateMachineDefinition(),
            $context
        );

        // Execute state machine
        $packet = $stateMachine->execute($input);

        return $packet;
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
