<?php

use Locospec\LCS\Specifications\SpecificationValidator;

// Update Operation Tests
test('valid update with shorthand filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'update',
        'tableName' => 'users',
        'filters' => [
            'id' => 1,
            'status' => 'active',
        ],
        'data' => [
            'status' => 'inactive',
            'updated_at' => '2024-01-01',
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('valid update with complex filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'update',
        'tableName' => 'users',
        'filters' => [
            'op' => 'and',
            'conditions' => [
                ['op' => 'eq', 'attribute' => 'status', 'value' => 'active'],
                ['op' => 'lt', 'attribute' => 'last_login', 'value' => '2024-01-01'],
            ],
        ],
        'data' => [
            'status' => 'inactive',
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('invalid update without data', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'update',
        'tableName' => 'users',
        'filters' => [
            'id' => 1,
        ],
        // missing data
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid update with empty data', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'update',
        'tableName' => 'users',
        'filters' => [
            'id' => 1,
        ],
        'data' => [],  // empty data
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

// Delete Operation Tests
test('valid delete with shorthand filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'delete',
        'tableName' => 'users',
        'filters' => [
            'id' => 1,
            'status' => 'inactive',
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('valid delete with complex filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'delete',
        'tableName' => 'users',
        'filters' => [
            'op' => 'or',
            'conditions' => [
                [
                    'op' => 'and',
                    'conditions' => [
                        ['op' => 'eq', 'attribute' => 'status', 'value' => 'inactive'],
                        ['op' => 'lt', 'attribute' => 'last_login', 'value' => '2023-01-01'],
                    ],
                ],
                ['op' => 'isNull', 'attribute' => 'email'],
            ],
        ],
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
})->group('stable');

test('invalid delete without filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'delete',
        'tableName' => 'users',
        // missing filters
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');

test('invalid delete with empty filters', function () {
    $validator = new SpecificationValidator;

    $operation = [
        'type' => 'delete',
        'tableName' => 'users',
        'filters' => [],  // empty filters
    ];

    $result = $validator->validateOperation($operation);
    expect($result['isValid'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
})->group('stable');
