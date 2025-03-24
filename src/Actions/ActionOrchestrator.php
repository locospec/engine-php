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
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\LCS;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\GeneratorInterface;
use Locospec\Engine\Registry\ValidatorInterface;
use Locospec\Engine\StateMachine\StateFlowPacket;
use Locospec\Engine\Views\ViewDefinition;
use Locospec\Engine\Actions\ActionDefinition;

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
            throw new InvalidArgumentException("Model/View/action not found: {$specName}");
        }

        $actionSpecName = $data->getType() === 'action' ? $data->getName() : '';
        $actionSpec = $this->lcs->getRegistryManager()->get('action', $actionSpecName);
        if ($data->getType() === 'action' && ! $actionSpec) {
            throw new InvalidArgumentException("Action Spec not found: {$actionSpec}");
        }

        $modelName = $data->getType() === 'view' ? $data->getModelName() : ($data->getType() === 'action' ? $data->getModelName() : $specName);
        $viewName = $data->getType() === 'model' ? $data->getName().'_default_view' : ($data->getType() === 'action' ? $data->getModelName().'_default_view' : $specName);
       
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
        $action = $this->createAction($curdValidator, $generator, $model, $view, $actionName, $actionSpec);

        return $action->execute($input);
    }

    protected function createAction(ValidatorInterface $curdValidator, GeneratorInterface $generator, ModelDefinition $model, ViewDefinition $view, string $actionName, ?ActionDefinition $actionSpec): ModelAction
    {
        $actionClass = match ($actionName) {
            '_config' => ConfigAction::class,
            '_read' => ReadListAction::class,
            '_read_relation_options' => ReadRelationOptionsAction::class,
            '_create' => CreateAction::class,
            '_update' => UpdateAction::class,
            'readOne' => ReadOneAction::class,
            'delete' => DeleteAction::class,
            default => throw new InvalidArgumentException("Unsupported action: {$actionName}")
        };

        return new $actionClass(
            $curdValidator,
            $generator,
            $model,
            $view,
            $actionSpec,
            $this->stateMachineFactory,
            $this->lcs
        );
    }
}
