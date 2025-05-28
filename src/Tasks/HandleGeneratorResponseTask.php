<?php

namespace LCSEngine\Tasks;

use LCSEngine\StateMachine\ContextInterface;

class HandleGeneratorResponseTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'handle_generator_response';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        return $input;
    }
}
