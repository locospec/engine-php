<?php

namespace LCSEngine\Tests\Schemas\Mutator;

use LCSEngine\Schemas\Mutator\SchemaProperty;

uses()->group('mutator');

test('can create schema property with required parameters', function () {
    $property = new SchemaProperty('string');

    expect($property)
        ->toBeInstanceOf(SchemaProperty::class)
        ->and($property->getType())->toBe('string')
        ->and($property->getDescription())->toBeNull();
});

test('can create schema property with optional description', function () {
    $property = new SchemaProperty('string', 'Test property');

    expect($property)
        ->toBeInstanceOf(SchemaProperty::class)
        ->and($property->getType())->toBe('string')
        ->and($property->getDescription())->toBe('Test property');
});

test('can create schema property from array', function () {
    $data = [
        'type' => 'string',
        'description' => 'Test property'
    ];

    $property = SchemaProperty::fromArray($data);

    expect($property)
        ->toBeInstanceOf(SchemaProperty::class)
        ->and($property->getType())->toBe('string')
        ->and($property->getDescription())->toBe('Test property');
});

test('can create schema property from array without description', function () {
    $data = [
        'type' => 'string'
    ];

    $property = SchemaProperty::fromArray($data);

    expect($property)
        ->toBeInstanceOf(SchemaProperty::class)
        ->and($property->getType())->toBe('string')
        ->and($property->getDescription())->toBeNull();
});

test('can convert schema property to array', function () {
    $property = new SchemaProperty('string', 'Test property');
    $array = $property->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['type', 'description'])
        ->and($array['type'])->toBe('string')
        ->and($array['description'])->toBe('Test property');
});

test('can convert schema property to array without description', function () {
    $property = new SchemaProperty('string');
    $array = $property->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['type'])
        ->and($array['type'])->toBe('string')
        ->and($array)->not->toHaveKey('description');
});
