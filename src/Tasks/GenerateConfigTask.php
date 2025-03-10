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

        // return the config, what else should be in the config?
        // $attributes = $this->context->get('model')->getAttributes()->toObject();
        // $filterable = $this->context->get('model')->getFilterable();

        // Get view
        $view = $this->context->get('view');

        $result = $view->toArray();

        return ['data' => $result];
    }
}
