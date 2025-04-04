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
            'StartAt' => 'PreparePayload',
            'States' => [
                'PreparePayload' => [
                    'Type' => 'Task',
                    'Resource' => 'prepare_payload',
                    'Next' => 'HandlePayload',
                ],
                'HandlePayload' => [
                    'Type' => 'Task',
                    'Resource' => 'handle_payload',
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
