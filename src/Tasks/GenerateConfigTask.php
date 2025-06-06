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
        // Get view
        $view = $this->context->get('view');
        $model = $this->context->get('model');
        $mutator = $this->context->get('mutator');

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
        } else {
            $result = $view->toArray();
            $permissions = $input['locospecPermissions'];
            $permissions['userPermissions'] = $input['globalContext']['userPermissions'];
            if ($permissions['isPermissionsEnabled'] && ! empty($permissions['userPermissions'])) {
                $userPermissions = $permissions['userPermissions'];
                // Filter items based on permissions
                if (isset($result['actions']->items)) {
                    $result['actions']->items = array_values(
                        array_filter(
                            array_map(function ($item) use ($userPermissions) {
                                if (isset($item->options)) {
                                    $filteredOptions = array_values(
                                        array_filter($item->options, function ($option) use ($userPermissions) {
                                            return in_array($option->key, $userPermissions);
                                        })
                                    );

                                    if (! empty($filteredOptions)) {
                                        $item->options = $filteredOptions;

                                        return $item;
                                    }

                                    return null;
                                }

                                return in_array($item->key, $userPermissions) ? $item : null;
                            }, $result['actions']->items)
                        )
                    );
                }
            }

            return ['data' => $result];
        }
    }
}
