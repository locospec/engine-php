<?php

namespace Locospec\LCS\Actions\Model;

class ReadOneAction extends ModelAction
{
    public static function getName(): string
    {
        return 'readOne';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'ValidateInput',
            'States' => [
                'ValidateInput' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'DatabaseRead'
                ],
                'DatabaseRead' => [
                    'Type' => 'Task',
                    'Resource' => 'database.select',
                    'Next' => 'CheckResult'
                ],
                'CheckResult' => [
                    'Type' => 'Choice',
                    'Choices' => [
                        [
                            'Variable' => '$.result',
                            'IsNull' => true,
                            'Next' => 'NotFound'
                        ]
                    ],
                    'Default' => 'Success'
                ],
                'NotFound' => [
                    'Type' => 'Task',
                    'Resource' => 'handle_not_found',
                    'End' => true
                ],
                'Success' => [
                    'Type' => 'Task',
                    'Resource' => 'transform_result',
                    'End' => true
                ]
            ]
        ];
    }
}
