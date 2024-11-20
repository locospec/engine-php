<?php

namespace Locospec\LCS\Actions;

use Locospec\LCS\Actions\Model\CreateAction;
use Locospec\LCS\Actions\Model\DeleteAction;
use Locospec\LCS\Actions\Model\ModelAction;
use Locospec\LCS\Actions\Model\ReadListAction;
use Locospec\LCS\Actions\Model\ReadOneAction;
use Locospec\LCS\Actions\Model\UpdateAction;
use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\LCS;
use Locospec\LCS\Models\ModelDefinition;
use Locospec\LCS\StateMachine\StateFlowPacket;

class ActionOrchestrator
{
    private LCS $lcs;

    private StateMachineFactory $stateMachineFactory;

    public function __construct(LCS $lcs, StateMachineFactory $stateMachineFactory)
    {
        $this->lcs = $lcs;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function execute(string $modelName, string $actionName, array $input = []): StateFlowPacket
    {
        // Get model definition
        $model = $this->lcs->getRegistryManager()->get('model', $modelName);
        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$modelName}");
        }

        // Create and execute appropriate action
        $action = $this->createAction($model, $actionName);

        return $action->execute($input);
    }

    protected function createAction(ModelDefinition $model, string $actionName): ModelAction
    {
        $actionClass = match ($actionName) {
            'create' => CreateAction::class,
            'readOne' => ReadOneAction::class,
            'readList' => ReadListAction::class,
            'update' => UpdateAction::class,
            'delete' => DeleteAction::class,
            default => throw new InvalidArgumentException("Unsupported action: {$actionName}")
        };

        return new $actionClass(
            $model,
            $this->stateMachineFactory,
            $this->lcs
        );
    }
}
