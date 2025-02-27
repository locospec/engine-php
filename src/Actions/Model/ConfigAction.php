<?php

namespace Locospec\Engine\Actions\Model;

/**
 * Standard Create action for models
 */
class ConfigAction extends ModelAction
{
    public static function getName(): string
    {
        return '_config';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'GenerateConfig',
            'States' => [
                'GenerateConfig' => [
                    'Type' => 'Task',
                    'Resource' => 'generate_config',
                    'End' => true,
                ],
            ],
        ];
    }
}
