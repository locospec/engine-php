<?php

namespace Locospec\Engine\Actions\Model;

use Locospec\Engine\Actions\StateMachineFactory;
use Locospec\Engine\Entities\EntityDefinition;
use Locospec\Engine\LCS;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Mutators\MutatorDefinition;
use Locospec\Engine\Registry\GeneratorInterface;
use Locospec\Engine\Registry\ValidatorInterface;
use Locospec\Engine\StateMachine\Context;
use Locospec\Engine\StateMachine\StateFlowPacket;
use Locospec\Engine\Views\ViewDefinition;

abstract class ModelAction
{
    protected ModelDefinition $model;

    protected ViewDefinition $view;

    protected ?MutatorDefinition $mutator;

    protected ?EntityDefinition $entity;

    protected array $config;

    protected string $name;

    protected StateMachineFactory $stateMachineFactory;

    protected LCS $lcs;

    public function __construct(
        ValidatorInterface $curdValidator,
        GeneratorInterface $generator,
        ModelDefinition $model,
        ViewDefinition $view,
        ?MutatorDefinition $mutator,
        ?EntityDefinition $entity,
        StateMachineFactory $stateMachineFactory,
        LCS $lcs,
        array $config = []
    ) {
        $this->model = $model;
        $this->view = $view;
        $this->mutator = $mutator;
        $this->entity = $entity;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->lcs = $lcs;
        $this->config = $config;
        $this->name = static::getName();
        $this->crudValidator = $curdValidator;
        $this->generator = $generator;
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
            'view' => $this->view,
            'mutator' => $this->mutator,
            'entity' => $this->entity,
            'attributes' => $this->model->getAttributes(),
            'action' => $this->name,
            'config' => $this->config,
            'lcs' => $this->lcs,
            'crudValidator' => $this->crudValidator,
            'generator' => $this->generator,
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
     * Get the validator
     */
    public function getCrudValidator(): ValidatorInterface
    {
        return $this->crudValidator;
    }

    /**
     * Get the generator
     */
    public function getGenerator(): GeneratorInterface
    {
        return $this->generator;
    }

    /**
     * Get the model definition this action operates on
     */
    public function getModel(): ModelDefinition
    {
        return $this->model;
    }

    /**
     * Get the view definition this action operates on
     */
    public function getView(): ViewDefinition
    {
        return $this->view;
    }

    /**
     * Get the action configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
