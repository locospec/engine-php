<?php

namespace Locospec\Engine\Actions;

use Locospec\Engine\Actions\Model\ConfigAction;
use Locospec\Engine\Actions\Model\CreateAction;
use Locospec\Engine\Actions\Model\DeleteAction;
use Locospec\Engine\Actions\Model\ModelAction;
use Locospec\Engine\Actions\Model\ReadListAction;
use Locospec\Engine\Actions\Model\ReadOneAction;
use Locospec\Engine\Actions\Model\ReadRelationOptionsAction;
use Locospec\Engine\Actions\Model\UpdateAction;
use Locospec\Engine\Entities\EntityDefinition;
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\LCS;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Mutators\MutatorDefinition;
use Locospec\Engine\Registry\GeneratorInterface;
use Locospec\Engine\Registry\ValidatorInterface;
use Locospec\Engine\StateMachine\StateFlowPacket;
use Locospec\Engine\Views\ViewDefinition;

class ActionOrchestrator
{
    private LCS $lcs;

    private StateMachineFactory $stateMachineFactory;

    public function __construct(LCS $lcs, StateMachineFactory $stateMachineFactory)
    {
        $this->lcs = $lcs;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function execute(ValidatorInterface $curdValidator, GeneratorInterface $generator, string $specName, string $actionName, array $input = []): StateFlowPacket
    {
        $data = $this->lcs->getRegistryManager()->getRegisterByName($specName);
        if (! $data) {
            throw new InvalidArgumentException("Model/View/Mutator not found: {$specName}");
        }

        $mutatorSpecName = $data->getType() === 'mutator' ? $data->getName() : '';
        $mutator = $this->lcs->getRegistryManager()->get('mutator', $mutatorSpecName);

        if ($data->getType() === 'mutator' && ! $mutator) {
            throw new InvalidArgumentException("Mutator Spec not found: {$mutatorSpecName}");
        }

        $entitySpecName = $data->getType() === 'entity' ? $data->getName() : '';
        $entity = $this->lcs->getRegistryManager()->get('entity', $entitySpecName);

        if ($data->getType() === 'entity' && ! $entity) {
            throw new InvalidArgumentException("Entity Spec not found: {$entitySpecName}");
        }

        $modelName = in_array($data->getType(), ['view', 'mutator', 'entity']) ? $data->getModelName() : $specName;
        $viewName = $data->getType() === 'model' ? (isset($input['view']) ? $input['view'] : $data->getName().'_default_view') : (in_array($data->getType(), ['mutator', 'entity']) ? $data->getModelName().'_default_view' : $specName);

        // Get model and view definition
        $model = $this->lcs->getRegistryManager()->get('model', $modelName);
        $view = $this->lcs->getRegistryManager()->get('view', $viewName);
        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$modelName}");
        }

        if (! $view) {
            throw new InvalidArgumentException("View not found: {$viewName}");
        }

        // Create and execute appropriate action
        $action = $this->createAction($curdValidator, $generator, $model, $view, $actionName, $mutator, $entity);

        return $action->execute($input);
    }

    protected function createAction(ValidatorInterface $curdValidator, GeneratorInterface $generator, ModelDefinition $model, ViewDefinition $view, string $actionName, ?MutatorDefinition $mutator, ?EntityDefinition $entity): ModelAction
    {
        // dd($actionName, $model);
        $actionClass = match ($actionName) {
            '_config' => ConfigAction::class,
            '_read' => ReadListAction::class,
            '_read_relation_options' => ReadRelationOptionsAction::class,
            '_create' => CreateAction::class,
            '_update' => UpdateAction::class,
            '_delete' => DeleteAction::class,
            'readOne' => ReadOneAction::class,
            default => throw new InvalidArgumentException("Unsupported action: {$actionName}")
        };

        return new $actionClass(
            $curdValidator,
            $generator,
            $model,
            $view,
            $mutator,
            $entity,
            $this->stateMachineFactory,
            $this->lcs
        );
    }
}
