<?php

namespace Locospec\Engine\Actions\Model;

/**
 * Standard Create action for models
 */
class CreateAction extends ModelAction
{
    public static function getName(): string
    {
        return '_create';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'PreparePayload',
            'States' => [
                'PreparePayload' => [
                    'Type' => 'Task',
                    'Resource' => 'prepare_payload',
                    'Next' => 'ValidatePayload',
                ],
                'ValidatePayload' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'HandlePayload',
                ],
                'HandlePayload' => [
                    'Type' => 'Task',
                    'Resource' => 'handle_payload',
                    'Next' => 'HandleResponse',
                ],
                'HandleResponse' => [
                    'Type' => 'Task',
                    'Resource' => 'handle_response',
                    'End' => true,
                ],
            ],
        ];
    }
}
