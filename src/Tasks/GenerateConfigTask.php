<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\StateMachine\ContextInterface;

class GenerateConfigTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'generate_config';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input): array
    {
        // Get view
        $view = $this->context->get('view');
        $actionSpec = $this->context->get('actionSpec');

        if(isset($actionSpec)){
            $result = $actionSpec->toArray();
            return ['data' => $result];
        }else{
            $result = $view->toArray();
            return ['data' => $result];
        }
    }
}
