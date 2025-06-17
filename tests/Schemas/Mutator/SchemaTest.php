<?php

namespace LCSEngine\Tests\Schemas\Mutator;

use LCSEngine\Schemas\Mutator\Schema;
use LCSEngine\Schemas\Mutator\SchemaType;
use LCSEngine\Schemas\Mutator\SchemaProperty;
use Illuminate\Support\Collection;

uses()->group('mutator');

test('can create schema with required parameters', function () {
    $schema = new Schema(
        SchemaType::OBJECT,
        new Collection(),
        new Collection()
    );

    expect($schema)
        ->toBeInstanceOf(Schema::class)
        ->and($schema->getType())->toBe(SchemaType::OBJECT)
        ->and($schema->getProperties())->toBeInstanceOf(Collection::class)
        ->and($schema->getProperties())->toBeEmpty()
        ->and($schema->getRequired())->toBeInstanceOf(Collection::class)
        ->and($schema->getRequired())->toBeEmpty();
});

test('can add and get properties', function () {
    $schema = new Schema(
        SchemaType::OBJECT,
        new Collection(),
        new Collection()
    );

    $property = new SchemaProperty('string', 'Test property');
    $schema->addProperty('test', $property);

    expect($schema->getProperties())
        ->toHaveCount(1)
        ->and($schema->getProperty('test'))->toBe($property);
});

test('can add and get required fields', function () {
    $schema = new Schema(
        SchemaType::OBJECT,
        new Collection(),
        new Collection()
    );

    $schema->addRequired('test');

    expect($schema->getRequired())
        ->toHaveCount(1)
        ->and($schema->getRequired()->first())->toBe('test');
});

test('can create schema from array', function () {
    $data = [
        'type' => 'object',
        'properties' => [
            'title' => [
                'type' => 'string',
                'description' => 'Title property'
            ],
            'description' => [
                'type' => 'text',
                'description' => 'Description property'
            ]
        ],
        'required' => ['title']
    ];

    $schema = Schema::fromArray($data);

    expect($schema)
        ->toBeInstanceOf(Schema::class)
        ->and($schema->getType())->toBe(SchemaType::OBJECT)
        ->and($schema->getProperties())->toHaveCount(2)
        ->and($schema->getProperties()->get('title'))->toBeInstanceOf(SchemaProperty::class)
        ->and($schema->getProperties()->get('description'))->toBeInstanceOf(SchemaProperty::class)
        ->and($schema->getRequired())->toHaveCount(1)
        ->and($schema->getRequired()->first())->toBe('title');
});

test('can convert schema to array', function () {
    $properties = collect([
        'test' => new SchemaProperty('string', 'Test property')
    ]);
    $required = collect(['test']);

    $schema = new Schema(
        SchemaType::OBJECT,
        $properties,
        $required
    );

    $array = $schema->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['type', 'properties', 'required'])
        ->and($array['type'])->toBe('object')
        ->and($array['properties'])->toBeArray()
        ->and($array['properties'])->toHaveCount(1)
        ->and($array['required'])->toBeArray()
        ->and($array['required'])->toHaveCount(1);
});

test('can create schema from array without optional fields', function () {
    $data = [
        'type' => 'object',
        'properties' => [],
        'required' => []
    ];

    $schema = Schema::fromArray($data);

    expect($schema)
        ->toBeInstanceOf(Schema::class)
        ->and($schema->getType())->toBe(SchemaType::OBJECT)
        ->and($schema->getProperties())->toBeEmpty()
        ->and($schema->getRequired())->toBeEmpty();
});
