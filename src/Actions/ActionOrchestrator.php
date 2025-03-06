<?php

namespace Locospec\Engine\Actions;

use Locospec\Engine\Actions\Model\ConfigAction;
use Locospec\Engine\Actions\Model\CreateAction;
use Locospec\Engine\Actions\Model\DeleteAction;
use Locospec\Engine\Actions\Model\ModelAction;
use Locospec\Engine\Actions\Model\ReadListAction;
use Locospec\Engine\Actions\Model\ReadOneAction;
use Locospec\Engine\Actions\Model\UpdateAction;
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\LCS;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Views\ViewDefinition;
use Locospec\Engine\StateMachine\StateFlowPacket;

class ActionOrchestrator
{
    private LCS $lcs;

    private StateMachineFactory $stateMachineFactory;

    public function __construct(LCS $lcs, StateMachineFactory $stateMachineFactory)
    {
        $this->lcs = $lcs;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function execute(string $modelViewName, string $actionName, array $input = []): StateFlowPacket
    {
        $data = $this->lcs->getRegistryManager()->getRegisterByName($modelViewName);

        if (! $data) {
            throw new InvalidArgumentException("Model/View not found: {$modelViewName}");
        }

        $modelName = $data->getType() === 'view'? $data->getModelName() : $modelViewName;
        $viewName = $data->getType() === 'model' ? $data->getName()."_default_view" : $modelViewName;

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
        $action = $this->createAction($model, $view, $actionName);

        return $action->execute($input);
    }

    protected function createAction(ModelDefinition $model, ViewDefinition $view, string $actionName): ModelAction
    {
        $actionClass = match ($actionName) {
            '_config' => ConfigAction::class,
            'create' => CreateAction::class,
            'readOne' => ReadOneAction::class,
            'readList' => ReadListAction::class,
            'update' => UpdateAction::class,
            'delete' => DeleteAction::class,
            default => throw new InvalidArgumentException("Unsupported action: {$actionName}")
        };

        return new $actionClass(
            $model,
            $view,
            $this->stateMachineFactory,
            $this->lcs
        );
    }
}
