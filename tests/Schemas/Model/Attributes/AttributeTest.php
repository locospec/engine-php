<?php

namespace LCSEngine\Tests\Schemas\Model\Attributes;

use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\AttributeType;
use LCSEngine\Schemas\Model\Attributes\Generator;
use LCSEngine\Schemas\Model\Attributes\GeneratorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;
use LCSEngine\Schemas\Model\Attributes\Option;
use LCSEngine\Schemas\Model\Attributes\Validator;
use LCSEngine\Schemas\Model\Attributes\ValidatorType;

uses()->group('attributes');

test('basic attribute creation', function () {
    $attribute = new Attribute;
    $attribute->setName('name')
        ->setType(AttributeType::UUID)
        ->setLabel('Name');

    expect($attribute->getName())->toBe('name')
        ->and($attribute->getType())->toBe(AttributeType::UUID)
        ->and($attribute->getLabel())->toBe('Name')
        ->and($attribute->getGenerators())->toBeNull()
        ->and($attribute->getValidators())->toBeNull()
        ->and($attribute->getOptions())->toBeNull()
        ->and($attribute->isPrimaryKey())->toBeFalse()
        ->and($attribute->isLabelKey())->toBeFalse()
        ->and($attribute->isDeleteKey())->toBeFalse()
        ->and($attribute->getAliasSource())->toBeNull()
        ->and($attribute->getAliasTransformation())->toBeNull();
});

test('attribute with primary key flag', function () {
    $attribute = new Attribute;
    $attribute->setName('id')
        ->setType(AttributeType::UUID)
        ->setLabel('ID')
        ->setPrimaryKey(true);

    expect($attribute->isPrimaryKey())->toBeTrue()
        ->and($attribute->toArray())->toHaveKey('primaryKey', true);
});

test('attribute with label key flag', function () {
    $attribute = new Attribute;
    $attribute->setName('title')
        ->setType(AttributeType::STRING)
        ->setLabel('Title')
        ->setLabelKey(true);

    expect($attribute->isLabelKey())->toBeTrue()
        ->and($attribute->toArray())->toHaveKey('labelKey', true);
});

test('attribute with delete key flag', function () {
    $attribute = new Attribute;
    $attribute->setName('deleted_at')
        ->setType(AttributeType::TIMESTAMP)
        ->setLabel('Deleted At')
        ->setDeleteKey(true);

    expect($attribute->isDeleteKey())->toBeTrue()
        ->and($attribute->toArray())->toHaveKey('deleteKey', true);
});

test('attribute with alias source and transformation', function () {
    $attribute = new Attribute;
    $attribute->setName('full_name')
        ->setType(AttributeType::ALIAS)
        ->setLabel('Full Name')
        ->setAliasSource('user')
        ->setAliasTransformation('firstName + " " + lastName');

    expect($attribute->getAliasSource())->toBe('user')
        ->and($attribute->getAliasTransformation())->toBe('firstName + " " + lastName')
        ->and($attribute->toArray())->toHaveKey('source', 'user')
        ->and($attribute->toArray())->toHaveKey('transform', 'firstName + " " + lastName');
});

test('attribute with generator', function () {
    $attribute = new Attribute;
    $attribute->setName('created_at')
        ->setType(AttributeType::TIMESTAMP)
        ->setLabel('Created At');

    $generator = new Generator;
    $generator->setType(GeneratorType::TIMESTAMP_GENERATOR)
        ->setOperations([OperationType::INSERT->value]);

    $attribute->addGenerator($generator);

    expect($attribute->getGenerators())->not->toBeNull()
        ->and($attribute->getGenerators()->count())->toBe(1)
        ->and($attribute->toArray())->toHaveKey('generations')
        ->and($attribute->toArray()['generations'][0]['type'])->toBe(GeneratorType::TIMESTAMP_GENERATOR->value)
        ->and($attribute->toArray()['generations'][0]['operations'])->toBe([OperationType::INSERT->value]);
});

test('attribute with validator', function () {
    $attribute = new Attribute;
    $attribute->setName('email')
        ->setType(AttributeType::STRING)
        ->setLabel('Email');

    $validator = new Validator;
    $validator->setType(ValidatorType::REQUIRED)
        ->setOperations([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->setMessage('Email is required');

    $attribute->addValidator($validator);

    expect($attribute->getValidators())->not->toBeNull()
        ->and($attribute->getValidators()->count())->toBe(1)
        ->and($attribute->toArray())->toHaveKey('validations')
        ->and($attribute->toArray()['validations'][0]['type'])->toBe(ValidatorType::REQUIRED->value)
        ->and($attribute->toArray()['validations'][0]['message'])->toBe('Email is required')
        ->and($attribute->toArray()['validations'][0]['operations'])->toBe([OperationType::INSERT->value, OperationType::UPDATE->value]);
});

test('attribute with options', function () {
    $attribute = new Attribute;
    $attribute->setName('status')
        ->setType(AttributeType::STRING)
        ->setLabel('Status');

    $option1 = new Option;
    $option1->setTitle('Active')
        ->setConst('active');

    $option2 = new Option;
    $option2->setTitle('Inactive')
        ->setConst('inactive');

    $attribute->addOption($option1)
        ->addOption($option2);

    expect($attribute->getOptions())->not->toBeNull()
        ->and($attribute->getOptions()->count())->toBe(2)
        ->and($attribute->toArray())->toHaveKey('options')
        ->and($attribute->toArray()['options'][0]['title'])->toBe('Active')
        ->and($attribute->toArray()['options'][0]['const'])->toBe('active')
        ->and($attribute->toArray()['options'][1]['title'])->toBe('Inactive')
        ->and($attribute->toArray()['options'][1]['const'])->toBe('inactive');
});

test('complete attribute example', function () {
    $attribute = new Attribute;
    $attribute->setName('user_id')
        ->setType(AttributeType::UUID)
        ->setLabel('User ID')
        ->setPrimaryKey(true);

    // Add generator
    $generator = new Generator;
    $generator->setType(GeneratorType::UUID_GENERATOR)
        ->setOperations([OperationType::INSERT->value]);
    $attribute->addGenerator($generator);

    // Add validator
    $validator = new Validator;
    $validator->setType(ValidatorType::REQUIRED)
        ->setOperations([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->setMessage('User ID is required');
    $attribute->addValidator($validator);

    $array = $attribute->toArray();
    expect($array)->toHaveKeys(['type', 'label', 'primaryKey', 'generations', 'validations'])
        ->and($array['type'])->toBe(AttributeType::UUID->value)
        ->and($array['label'])->toBe('User ID')
        ->and($array['primaryKey'])->toBeTrue()
        ->and($array['generations'][0]['type'])->toBe(GeneratorType::UUID_GENERATOR->value)
        ->and($array['validations'][0]['type'])->toBe(ValidatorType::REQUIRED->value);
});
