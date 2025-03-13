<?php

namespace Locospec\Engine\Actions\Model;

class UpdateAction extends ModelAction
{
    public static function getName(): string
    {
        return '_update';
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
                    // 'Next' => 'HandleResponse',
                    'End' => true,
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
