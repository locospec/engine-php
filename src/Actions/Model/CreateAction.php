<?php

namespace Locospec\Engine\Actions\Model;

/**
 * Standard Create action for models
 */
class CreateAction extends ModelAction
{
    public static function getName(): string
    {
        return 'create';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'CleanInput',
            'States' => [
                'CleanInput' => [
                    'Type' => 'Task',
                    'Resource' => 'clean_input',
                    'Next' => 'ValidateInput',
                ],
                'ValidateInput' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'GenerateAttributes',
                ],
                'GenerateAttributes' => [
                    'Type' => 'Task',
                    'Resource' => 'generate_attributes',
                    'Next' => 'DatabaseInsert',
                ],
                'DatabaseInsert' => [
                    'Type' => 'Task',
                    'Resource' => 'database.insert',
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
