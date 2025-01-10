<?php

use Locospec\LCS\Specifications\SpecificationValidator;

beforeAll(function () {
    global $validator;
    $validator = new SpecificationValidator;
});

it('uses shared data', function () {
    global $validator;
    expect($validator)->toBeInstanceOf(SpecificationValidator::class);
})->group('stable');

test('valid insert operation with single row', function () {
    // $validator = new SpecificationValidator();
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com'],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('valid insert operation with multiple rows', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('invalid insert operation without type', function () {
    global $validator;

    $operation = [
        'tableName' => 'users',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com'],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation without table name', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'data' => [
            ['name' => 'John', 'email' => 'john@example.com'],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation with empty data array', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation with wrong data type', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => 'not an array',
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation with non-object data items', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            'just a string',
            ['this is okay'],
            123,
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation with extra properties', function () {
    global $validator;

    $operation = [
        'type' => 'insert',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John'],
        ],
        'extraProperty' => 'should not be here',
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid insert operation with wrong type value', function () {
    global $validator;

    $operation = [
        'type' => 'invalid_type',
        'tableName' => 'users',
        'data' => [
            ['name' => 'John'],
        ],
    ];

    $result = $validator->validateOperation($operation);

    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');
