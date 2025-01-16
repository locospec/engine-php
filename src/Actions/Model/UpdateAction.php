<?php

namespace Locospec\Engine\Actions\Model;

class UpdateAction extends ModelAction
{
    public static function getName(): string
    {
        return 'update';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'ValidateInput',
            'States' => [
                'ValidateInput' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'DatabaseUpdate',
                ],
                'DatabaseUpdate' => [
                    'Type' => 'Task',
                    'Resource' => 'database.update',
                    'Next' => 'DatabaseRead',
                ],
                'DatabaseRead' => [
                    'Type' => 'Task',
                    'Resource' => 'database.select',
                    'End' => true,
                ],
            ],
        ];
    }
}
