<?php

namespace LCSEngine\Actions;

use LCSEngine\Actions\Model\CustomAction;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
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

    public function execute(string $specName, array $input = []): StateFlowPacket
    {
        $model = $this->lcs->getRegistryManager()->get('model', $specName);
        $query = $this->lcs->getRegistryManager()->get('query', $specName.'_default_query');

        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$specName}");
        }

        $action = new CustomAction($model, $query, null, $this->stateMachineFactory, $this->lcs);

        return $action->execute($input);
    }
}
