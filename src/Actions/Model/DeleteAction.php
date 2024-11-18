<?php

namespace Locospec\LCS\Actions\Model;

class DeleteAction extends ModelAction
{
    public static function getName(): string
    {
        return 'delete';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'ValidateInput',
            'States' => [
                'ValidateInput' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'CheckSoftDelete'
                ],
                'CheckSoftDelete' => [
                    'Type' => 'Choice',
                    'Choices' => [
                        [
                            'Variable' => '$.config.softDelete',
                            'BooleanEquals' => true,
                            'Next' => 'SoftDelete'
                        ]
                    ],
                    'Default' => 'HardDelete'
                ],
                'SoftDelete' => [
                    'Type' => 'Task',
                    'Resource' => 'database.soft_delete',
                    'End' => true
                ],
                'HardDelete' => [
                    'Type' => 'Task',
                    'Resource' => 'database.delete',
                    'End' => true
                ]
            ]
        ];
    }
}
