<?php

use Locospec\Engine\SpecValidator;

beforeAll(function () {
    global $validator;
    $validator = new SpecValidator;
});

it('uses shared data', function () {
    global $validator;
    expect($validator)->toBeInstanceOf(SpecValidator::class);
})->group('stable');

test('select with minimal requirements', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with shorthand filters', function () {
    global $validator;

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

    // dd($result);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with full form filters - single condition', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'is',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with full form filters - multiple conditions', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'is',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
                [
                    'op' => 'greater_than',
                    'attribute' => 'age',
                    'value' => 18,
                ],
                [
                    'op' => 'contains',
                    'attribute' => 'name',
                    'value' => 'John%',
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with nested filter groups', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'or',
            'conditions' => [
                [
                    'op' => 'is',
                    'attribute' => 'status',
                    'value' => 'active',
                ],
                [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'op' => 'greater_than',
                            'attribute' => 'age',
                            'value' => 18,
                        ],
                        [
                            'op' => 'less_than',
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
})->group('stable');

test('select with all possible filter operators', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'is', 'attribute' => 'field1', 'value' => 'value1'],
                ['op' => 'is_not', 'attribute' => 'field2', 'value' => 'value2'],
                ['op' => 'greater_than', 'attribute' => 'field3', 'value' => 100],
                ['op' => 'less_than', 'attribute' => 'field4', 'value' => 200],
                ['op' => 'greater_than_or_equal', 'attribute' => 'field5', 'value' => 300],
                ['op' => 'less_than_or_equal', 'attribute' => 'field6', 'value' => 400],
                ['op' => 'contains', 'attribute' => 'field7', 'value' => '%value%'],
                ['op' => 'not_contains', 'attribute' => 'field8', 'value' => '%value%'],
                ['op' => 'is_any_of', 'attribute' => 'field9', 'value' => [1, 2, 3]],
                ['op' => 'is_none_of', 'attribute' => 'field10', 'value' => [4, 5, 6]],
                ['op' => 'is_empty', 'attribute' => 'field11'],
                ['op' => 'is_not_empty', 'attribute' => 'field12'],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with complete configuration', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'is', 'attribute' => 'status', 'value' => 'active'],
            ],
        ],
        'sorts' => [
            ['attribute' => 'created_at', 'direction' => 'DESC'],
            ['attribute' => 'name', 'direction' => 'ASC'],
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
})->group('stable');

test('select with direct filters configuration', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            ['op' => 'is', 'attribute' => 'status', 'value' => 'active'],
            ['op' => 'is', 'attribute' => 'status', 'value' => 'active'],
        ],
        'sorts' => [
            ['attribute' => 'created_at', 'direction' => 'DESC'],
            ['attribute' => 'name', 'direction' => 'ASC'],
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
})->group('stable');

// Invalid cases

test('select with invalid filter operator', function () {
    global $validator;

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
})->group('stable');

test('select with invalid shorthand filter value type', function () {
    global $validator;

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
})->group('stable');

test('select with invalid filter group structure', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'conditions' => [  // Missing 'op' in nested group
                        [
                            'op' => 'is',
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
})->group('stable');

test('select with missing attribute in filter condition', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                [
                    'op' => 'is',
                    'value' => 'active',  // Missing 'attribute'
                ],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('select with cursor pagination', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'sorts' => [
            ['attribute' => 'created_at', 'direction' => 'DESC'],
        ],
        'pagination' => [
            'type' => 'cursor',
            'per_page' => 20,
            'cursor' => 'encoded_cursor_value',
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('select with cursor pagination and empty sorts', function () {
    global $validator;

    $operation = [
        'type' => 'select',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'is', 'attribute' => 'status', 'value' => 'active'],
            ],
        ],
        'pagination' => [
            'type' => 'cursor',
            'per_page' => 20,
            'cursor' => 'encoded_cursor_value',
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');
