<?php

namespace LCSEngine\Actions\Model;

use LCSEngine\Actions\StateMachineFactory;
use LCSEngine\LCS;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Mutator\Mutator;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\StateMachine\Context;
use LCSEngine\StateMachine\StateFlowPacket;

abstract class ModelAction
{
    protected Model $model;

    protected Query $query;

    protected ?Mutator $mutator;

    protected array $config;

    protected string $name;

    protected StateMachineFactory $stateMachineFactory;

    protected LCS $lcs;

    public function __construct(
        Model $model,
        Query $query,
        ?Mutator $mutator,
        StateMachineFactory $stateMachineFactory,
        LCS $lcs,
        array $config = []
    ) {
        $this->model = $model;
        $this->query = $query;
        $this->mutator = $mutator;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->lcs = $lcs;
        $this->config = $config;
        $this->name = static::getName();
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
        // Create context with required values
        $context = new Context([
            'model' => $this->model,
            'query' => $this->query,
            'mutator' => $this->mutator,
            'attributes' => $this->model->getAttributes(),
            'action' => $this->name,
            'config' => $this->config,
            'lcs' => $this->lcs,
        ]);

        if (isset($input['def'])) {
            // Create state machine via factory
            $stateMachine = $this->stateMachineFactory->create(
                $this->getStateMachineDefinition(json_decode(json_encode($input['def']), true)),
                $context
            );

            // Execute state machine
            $packet = $stateMachine->execute($input);

            return $packet;
        } else {
            $stateMachine = $this->stateMachineFactory->create(
                $this->getStateMachineDefinition(),
                $context
            );

            // Execute state machine
            $packet = $stateMachine->execute($input);

            return $packet;
        }
    }

    /**
     * Get the model definition this action operates on
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the query this action operates on
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Get the action configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
