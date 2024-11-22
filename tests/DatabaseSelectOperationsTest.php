<?php

use Locospec\LCS\Database\Validators\DatabaseOperationsValidator;

test('select with minimal requirements', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with shorthand filters', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'status' => 'active',
            'age' => 25,
            'is_admin' => true,
            'deleted_at' => null,
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with full form filters - single condition', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'eq',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with full form filters - multiple conditions', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'eq',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
                [
                    'op' => 'gt',
                    'attribute' => 'age',
                    'value' => 18,
                ],
                [
                    'op' => 'like',
                    'attribute' => 'name',
                    'value' => 'John%',
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with nested filter groups', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'or',
            'conditions' => [
                [
                    'op' => 'eq',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
                [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'op' => 'gt',
                            'attribute' => 'age',
                            'value' => 18,
                        ],
                        [
                            'op' => 'lt',
                            'attribute' => 'age',
                            'value' => 65,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with all possible filter operators', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'eq', 'attribute' => 'field1', 'value' => 'value1'],
                ['op' => 'neq', 'attribute' => 'field2', 'value' => 'value2'],
                ['op' => 'gt', 'attribute' => 'field3', 'value' => 100],
                ['op' => 'lt', 'attribute' => 'field4', 'value' => 200],
                ['op' => 'gte', 'attribute' => 'field5', 'value' => 300],
                ['op' => 'lte', 'attribute' => 'field6', 'value' => 400],
                ['op' => 'like', 'attribute' => 'field7', 'value' => '%value%'],
                ['op' => 'notLike', 'attribute' => 'field8', 'value' => '%value%'],
                ['op' => 'in', 'attribute' => 'field9', 'value' => [1, 2, 3]],
                ['op' => 'notIn', 'attribute' => 'field10', 'value' => [4, 5, 6]],
                ['op' => 'isNull', 'attribute' => 'field11'],
                ['op' => 'isNotNull', 'attribute' => 'field12'],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('select with complete configuration', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'eq', 'attribute' => 'status', 'value' => 'active'],
            ],
        ],
        'sorts' => [
            ['attribute' => 'created_at', 'order' => 'DESC'],
            ['attribute' => 'name', 'order' => 'ASC'],
        ],
        'attributes' => ['id', 'name', 'email', 'status'],
        'pagination' => [
            'type' => 'offset',
            'page' => 1,
            'per_page' => 20,
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

// Invalid cases

test('select with invalid filter operator', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'invalid_operator',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('select with invalid shorthand filter value type', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'status' => ['this', 'should', 'not', 'be', 'an', 'array'],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('select with invalid filter group structure', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'conditions' => [  // Missing 'op' in nested group
                        [
                            'op' => 'eq',
                            'attribute' => 'status',
                            'value' => 'active',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('select with missing attribute in filter condition', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'eq',
                    'value' => 'active',  // Missing 'attribute'
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('select with cursor pagination', function () {
    $validator = new DatabaseOperationsValidator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'pagination' => [
            'type' => 'cursor',
            'per_page' => 20,
            'cursor' => 'encoded_cursor_value',
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});
