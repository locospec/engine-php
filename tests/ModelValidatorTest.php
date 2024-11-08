<?php

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Models\ModelDefinition;

test('model names are properly validated', function ($name, $shouldPass) {
    $data = [
        'type' => 'model',
        'name' => $name,
    ];

    if (! $shouldPass) {
        expect(fn () => ModelDefinition::fromArray($data))
            ->toThrow(InvalidArgumentException::class);
    } else {
        expect(fn () => ModelDefinition::fromArray($data))
            ->not->toThrow(InvalidArgumentException::class);
    }
})->with([
    'valid simple name' => ['user', true],
    'valid simple name 1' => ['city', true],
    'invalid plural name 1' => ['cities', false],
    'valid with underscore' => ['blog_post', true],
    'valid with hyphen' => ['product-category', true],
    'valid with hyphen 1' => ['product-categories', false],
    'invalid with spaces' => ['user profile', false],
    'invalid with uppercase' => ['UserProfile', false],
    'invalid plural' => ['users', false],
    'invalid with spaces and uppercase' => ['User Profile', false],
    'invalid with special chars' => ['user@profile', false],
    'invalid empty' => ['', false],
    'invalid numeric' => ['123user', false],
]);

test('relationships are properly validated', function () {
    $data = [
        'type' => 'model',
        'name' => 'post',
        'relationships' => [
            'belongs_to' => [
                'user' => [
                    'model' => 'user',
                    'foreignKey' => 'user_id',
                ],
                'category' => [
                    'model' => 'category',
                ],
            ],
        ],
    ];

    expect(fn () => ModelDefinition::fromArray($data))
        ->not->toThrow(InvalidArgumentException::class);

    // Invalid relationship model name
    $data['relationships']['belongs_to']['user']['model'] = 'Invalid Model';
    expect(fn () => ModelDefinition::fromArray($data))
        ->toThrow(InvalidArgumentException::class);
});

test('configuration is properly validated', function () {
    // Valid config
    $validData = [
        'type' => 'model',
        'name' => 'user',
        'config' => [
            'primaryKey' => 'id',
            'table' => 'users',
        ],
    ];

    expect(fn () => ModelDefinition::fromArray($validData))
        ->not->toThrow(InvalidArgumentException::class);

    // Invalid config (not an array)
    $invalidData = [
        'type' => 'model',
        'name' => 'user',
        'config' => 'invalid',
    ];

    expect(fn () => ModelDefinition::fromArray($invalidData))
        ->toThrow(InvalidArgumentException::class);
});
