<?php

namespace LCSEngine\Actions;

use LCSEngine\Actions\Model\ConfigAction;
use LCSEngine\Actions\Model\CreateAction;
use LCSEngine\Actions\Model\DeleteAction;
use LCSEngine\Actions\Model\ModelAction;
use LCSEngine\Actions\Model\ReadListAction;
use LCSEngine\Actions\Model\ReadOneAction;
use LCSEngine\Actions\Model\ReadRelationOptionsAction;
use LCSEngine\Actions\Model\UpdateAction;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
use LCSEngine\Registry\GeneratorInterface;
use LCSEngine\Registry\ValidatorInterface;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Mutator\Mutator;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Type;
use LCSEngine\StateMachine\StateFlowPacket;

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
        $mutator = null;
        $data = $this->lcs->getRegistryManager()->getRegisterByName($specName);

        if (! $data) {
            throw new InvalidArgumentException("Query/Mutator/Model not found: {$specName}");
        }

        if ($data->getType() === Type::MUTATOR) {
            $mutator = $data;
        }

        $type = $data->getType();
        $isQueryOrMutator = in_array($type, [Type::QUERY, Type::MUTATOR]);

        if ($actionName === '_create' && $type === Type::MODEL) {
            $mutatorName = match ($type) {
                Type::MODEL => $data->getName().'_default_create_mutator',
                default => $specName
            };

            try {
                $mutator = $this->lcs->getRegistryManager()->get('mutator', $mutatorName);
            } catch (\Throwable $th) {
                $mutator = null;
            }
        }

        if ($actionName === '_update' && $type === Type::MODEL) {
            $mutatorName = match ($type) {
                Type::MODEL => $data->getName().'_default_update_mutator',
                default => $specName
            };

            try {
                $mutator = $this->lcs->getRegistryManager()->get('mutator', $mutatorName);
            } catch (\Throwable $th) {
                $mutator = null;
            }
        }

        if ($actionName === '_delete' && $type === Type::MODEL) {
            $mutatorName = match ($type) {
                Type::MODEL => $data->getName().'_default_delete_mutator',
                default => $specName
            };

            try {
                $mutator = $this->lcs->getRegistryManager()->get('mutator', $mutatorName);
            } catch (\Throwable $th) {
                $mutator = null;
            }
        }

        $modelName = $isQueryOrMutator ? $data->getModelName() : $specName;

        $queryName = match ($type) {
            Type::MODEL => $input['query'] ?? $data->getName().'_default_query',
            Type::MUTATOR => $data->getModelName().'_default_query',
            default => $specName
        };

        $model = $this->lcs->getRegistryManager()->get('model', $modelName);
        $query = $this->lcs->getRegistryManager()->get('query', $queryName);
        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$modelName}");
        }

        if (! $query) {
            throw new InvalidArgumentException("Query not found: {$queryName}");
        }

        // Create and execute appropriate action
        $action = $this->createAction($curdValidator, $generator, $model, $query, $actionName, $mutator);

        return $action->execute($input);
    }

    protected function createAction(ValidatorInterface $curdValidator, GeneratorInterface $generator, Model $model, Query $query, string $actionName, ?Mutator $mutator): ModelAction
    {
        $actionClass = match ($actionName) {
            '_config' => ConfigAction::class,
            '_read' => ReadListAction::class,
            '_read_relation_options' => ReadRelationOptionsAction::class,
            '_create' => CreateAction::class,
            '_update' => UpdateAction::class,
            '_delete' => DeleteAction::class,
            '_read_one' => ReadOneAction::class,
            default => throw new InvalidArgumentException("Unsupported action: {$actionName}")
        };

        return new $actionClass(
            $curdValidator,
            $generator,
            $model,
            $query,
            $mutator,
            $this->stateMachineFactory,
            $this->lcs
        );
    }
}
