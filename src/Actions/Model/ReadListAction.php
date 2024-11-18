<?php

namespace Locospec\LCS\Actions\Model;

class ReadListAction extends ModelAction
{
    public static function getName(): string
    {
        return 'readList';
    }

    protected function getStateMachineDefinition(): array
    {
        return [
            'StartAt' => 'ValidateInput',
            'States' => [
                'ValidateInput' => [
                    'Type' => 'Task',
                    'Resource' => 'validate',
                    'Next' => 'CheckPagination'
                ],
                'CheckPagination' => [
                    'Type' => 'Choice',
                    'Choices' => [
                        [
                            'Variable' => '$.pagination',
                            'IsNull' => false,
                            'Next' => 'DatabasePaginate'
                        ]
                    ],
                    'Default' => 'DatabaseSelect'
                ],
                'DatabasePaginate' => [
                    'Type' => 'Task',
                    'Resource' => 'database.paginate',
                    'Next' => 'TransformResults'
                ],
                'DatabaseSelect' => [
                    'Type' => 'Task',
                    'Resource' => 'database.select',
                    'Next' => 'TransformResults'
                ],
                'TransformResults' => [
                    'Type' => 'Task',
                    'Resource' => 'transform_results',
                    'End' => true
                ]
            ]
        ];
    }
}
