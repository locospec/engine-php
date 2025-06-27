<?php

namespace LCSEngine\Tests\Schemas\Mutator;

use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Option;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;
use LCSEngine\Schemas\Model\Attributes\Validator;
use LCSEngine\Schemas\Model\Attributes\ValidatorType;
use LCSEngine\Schemas\Mutator\Schema;

uses()->group('mutator');

test('can create schema from attributes', function () {
    $attributes = collect([
        'title' => new Attribute('title', 'Title', AttributeType::STRING),
        'description' => new Attribute('description', 'Description', AttributeType::TEXT),
        'age' => new Attribute('age', 'Age', AttributeType::INTEGER),
        'is_active' => new Attribute('is_active', 'Is Active', AttributeType::BOOLEAN),
        'created_at' => new Attribute('created_at', 'Created At', AttributeType::TIMESTAMP),
    ]);

    $schema = Schema::fromAttributes($attributes);

    expect($schema)
        ->toBeInstanceOf(Schema::class)
        ->and($schema->toArray())->toBeArray()
        ->and($schema->toArray())->toHaveKeys(['type', 'properties'])
        ->and($schema->toArray()['type'])->toBe('object')
        ->and($schema->toArray()['properties'])->toBeArray()
        ->and($schema->toArray()['properties'])->toHaveCount(5)
        ->and($schema->toArray()['properties']['title']['type'])->toBe('string')
        ->and($schema->toArray()['properties']['description']['type'])->toBe('string')
        ->and($schema->toArray()['properties']['age']['type'])->toBe('number')
        ->and($schema->toArray()['properties']['is_active']['type'])->toBe('boolean')
        ->and($schema->toArray()['properties']['created_at']['type'])->toBe('string')
        ->and($schema->toArray()['properties']['created_at']['format'])->toBe('date-time');
});

test('can add property with required validator', function () {
    $schema = new Schema;
    $attribute = new Attribute('title', 'Title', AttributeType::STRING);
    $attribute->addValidator(new Validator(ValidatorType::REQUIRED));

    $schema->addProperty($attribute);

    expect($schema->toArray())
        ->toBeArray()
        ->toHaveKeys(['type', 'properties', 'required'])
        ->and($schema->toArray()['required'])->toBeArray()
        ->and($schema->toArray()['required'])->toContain('title');
});

test('can add property with related model', function () {
    $schema = new Schema;
    $attribute = new Attribute('user_id', 'User', AttributeType::ID);
    $attribute->setRelatedModelName('User');

    $schema->addProperty($attribute);

    expect($schema->toArray())
        ->toBeArray()
        ->and($schema->toArray()['properties']['user_id']['relatedModelName'])->toBe('User');
});

test('can add property with dependencies', function () {
    $schema = new Schema;
    $attribute = new Attribute('role_id', 'Role', AttributeType::ID);
    $attribute->setDependsOn('user_id');
    $attribute->setDependsOn('department_id');

    $schema->addProperty($attribute);

    expect($schema->toArray())
        ->toBeArray()
        ->and($schema->toArray()['properties']['role_id']['dependsOn'])->toBeArray()
        ->and($schema->toArray()['properties']['role_id']['dependsOn'])->toHaveCount(2)
        ->and($schema->toArray()['properties']['role_id']['dependsOn'])->toContain('user_id', 'department_id');
});

test('can add property with options', function () {
    $schema = new Schema;
    $attribute = new Attribute('status', 'Status', AttributeType::STRING);
    $option1 = new Option;
    $option1->setConst('active');
    $option1->setTitle('Active');
    $option2 = new Option;
    $option2->setConst('inactive');
    $option2->setTitle('Inactive');
    $attribute->addOption($option1);
    $attribute->addOption($option2);

    $schema->addProperty($attribute);

    expect($schema->toArray())
        ->toBeArray()
        ->and($schema->toArray()['properties']['status']['options'])->toBeArray()
        ->and($schema->toArray()['properties']['status']['options'])->toHaveCount(2)
        ->and($schema->toArray()['properties']['status']['options'][0]['const'])->toBe('active')
        ->and($schema->toArray()['properties']['status']['options'][0]['title'])->toBe('Active')
        ->and($schema->toArray()['properties']['status']['options'][1]['const'])->toBe('inactive')
        ->and($schema->toArray()['properties']['status']['options'][1]['title'])->toBe('Inactive');
});