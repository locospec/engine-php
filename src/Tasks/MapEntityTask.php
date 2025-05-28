<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\JMESPathCustomRuntime;
use LCSEngine\StateMachine\ContextInterface;

class MapEntityTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'map_entity';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        $template = $taskArgs['template'] ?? [];

        $data = [
            'result' => $input['result'] ?? null,
            'payload' => $input['payload'] ?? null,
        ];

        $mapped = [];
        $runtime = new JMESPathCustomRuntime;

        foreach ($template as $outputKey => $expression) {
            $mapped[$outputKey] = $runtime->search($expression, $data);
        }

        $input['result']['mappedData'] = $mapped;

        return $input;
    }
}
