<?php

namespace LCSEngine\Actions\Model;

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
            'StartAt' => 'CheckPermission',
            'States' => [
                'CheckPermission' => [
                    'Type' => 'Task',
                    'Resource' => 'check_permission',
                    'Next' => 'GenerateConfig',
                ],
                'GenerateConfig' => [
                    'Type' => 'Task',
                    'Resource' => 'generate_config',
                    'End' => true,
                ],
            ],
        ];
    }
}
