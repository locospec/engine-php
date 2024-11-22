<?php

use Locospec\LCS\Database\Validators\DatabaseOperationsValidator;

test('valid insert operation with single row', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com']
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('valid insert operation with multiple rows', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com']
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

test('invalid insert operation without type', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com']
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation without table name', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com']
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation with empty data array', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => []
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation with wrong data type', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => 'not an array'
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation with non-object data items', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            'just a string',
            ['this is okay'],
            123
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation with extra properties', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John']
        ],
        'extraProperty' => 'should not be here'
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

test('invalid insert operation with wrong type value', function () {
    $validator = new DatabaseOperationsValidator();

    $operation = [
        'type' => 'invalid_type',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John']
        ]
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});
