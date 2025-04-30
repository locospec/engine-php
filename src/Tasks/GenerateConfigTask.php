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

    public function execute(array $input, array $taskArgs=[]): array
    {
        // Get view
        $view = $this->context->get('view');
        $model = $this->context->get('model');
        $mutator = $this->context->get('mutator');
        $entity = $this->context->get('entity');

        if (isset($mutator)) {
            $result = $mutator->toArray();
            $schema = $mutator->getSchema();
            $keys = array_keys((array) $schema['properties']);

            if (isset($input['response']) && ! empty($input['response']) && ! empty($input['response'][0]['result'])) {
                $data = $input['response'][0]['result'][0];

                foreach ($keys as $key) {
                    if (array_key_exists($key, $data)) {
                        $result['initialData'][$key] = $data[$key];
                    }
                }

                $result['initialData'][$model->getConfig()->getPrimaryKey()] = $data[$model->getConfig()->getPrimaryKey()];
            }

            return ['data' => $result];
        } elseif (isset($entity)) {
            $result = $entity->toArray();
            $result['initialData'] = $input['response'][0]['result'][0];

            return ['data' => $result];
        } else {
            $result = $view->toArray();

            return ['data' => $result];
        }
    }
}
