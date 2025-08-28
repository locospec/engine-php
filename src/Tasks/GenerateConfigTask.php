<?php

namespace LCSEngine\Tasks;

use LCSEngine\StateMachine\ContextInterface;

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

    public function execute(array $input, array $taskArgs = []): array
    {
        $query = $this->context->get('query');
        $model = $this->context->get('model');
        $mutator = $this->context->get('mutator');

        if (isset($mutator)) {
            $result = $mutator->toArray();

            return ['data' => $result];
        } else {
            $result = $query->toArray();

            // Add model aggregates to the config response
            $aggregates = $model->getAggregates()->mapWithKeys(function ($aggregate, $key) {
                return [$key => $aggregate->toArray()];
            })->all();

            if (! empty($aggregates)) {
                $result['aggregates'] = $aggregates;
            }

            $result['primaryKey'] = $model->getPrimaryKey()->getName();

            return ['data' => $result];
        }
    }
}
