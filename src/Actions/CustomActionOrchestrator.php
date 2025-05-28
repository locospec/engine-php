<?php

namespace LCSEngine\Actions;

use LCSEngine\Actions\Model\CustomAction;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
use LCSEngine\Registry\GeneratorInterface;
use LCSEngine\Registry\ValidatorInterface;
use LCSEngine\StateMachine\StateFlowPacket;

class CustomActionOrchestrator
{
    private LCS $lcs;

    private StateMachineFactory $stateMachineFactory;

    public function __construct(LCS $lcs, StateMachineFactory $stateMachineFactory)
    {
        $this->lcs = $lcs;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function execute(ValidatorInterface $curdValidator, GeneratorInterface $generator, string $specName, array $input = []): StateFlowPacket
    {
        $model = $this->lcs->getRegistryManager()->get('model', $specName);
        $view = $this->lcs->getRegistryManager()->get('view', $specName.'_default_view');

        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$specName}");
        }

        $action = new CustomAction(
            $curdValidator,
            $generator,
            $model,
            $view,
            null,
            null,
            $this->stateMachineFactory,
            $this->lcs
        );

        return $action->execute($input);
    }
}
