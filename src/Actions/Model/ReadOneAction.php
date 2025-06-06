<?php

namespace LCSEngine\Actions\Model;

class ReadOneAction extends ModelAction
{
    public static function getName(): string
    {
        return '_read_one';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'CheckPermission',
            'States' => [
                'CheckPermission' => [
                    'Type' => 'Task',
                    'Resource' => 'check_permission',
                    'Next' => 'PreparePayload',
                ],
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
