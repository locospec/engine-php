<?php

use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Generator;
use LCSEngine\Schemas\Model\Attributes\GeneratorType;
use LCSEngine\Schemas\Model\Attributes\Option;
use LCSEngine\Schemas\Model\Attributes\Type;
use LCSEngine\Schemas\Model\Attributes\Validator;
use LCSEngine\Schemas\Model\Attributes\ValidatorType;

uses()->group('attributes');

test('can create and get/set all fields', function () {
    $attribute = new Attribute('username', 'Username', Type::STRING);
    $attribute->setPrimaryKey(true);
    $attribute->setLabelKey(true);
    $attribute->setDeleteKey(true);
    $attribute->setRelatedModelName('User');
    $attribute->setDependsOn('user_id');
    expect($attribute->getName())->toBe('username')
        ->and($attribute->getLabel())->toBe('Username')
        ->and($attribute->getType())->toBe(Type::STRING)
        ->and($attribute->getRelatedModelName())->toBe('User')
        ->and($attribute->getDependsOn())->toHaveCount(1)
        ->and($attribute->getDependsOn()->first())->toBe('user_id');
    expect($attribute->getGenerators())->toBeEmpty();
    expect($attribute->getValidators())->toBeEmpty();
    expect($attribute->getOptions())->toBeEmpty();
});

test('setAliasSource and setAliasTransformation throw if not alias', function () {
    $attribute = new Attribute('username', 'Username', Type::STRING);
    expect(fn () => $attribute->setAliasSource('user.name'))->toThrow(LogicException::class);
    expect(fn () => $attribute->setAliasTransformation('upper(user.name)'))->toThrow(LogicException::class);
});

test('setAliasSource and setAliasTransformation work for alias', function () {
    $attribute = new Attribute('alias', 'Alias', Type::ALIAS);
    $attribute->setAliasSource('user.name');
    $attribute->setAliasTransformation('upper(user.name)');
    expect($attribute->getAliasSource())->toBe('user.name')
        ->and($attribute->getAliasTransformation())->toBe('upper(user.name)');
});

test('can add generators, validators, and options', function () {
    $attribute = new Attribute('username', 'Username', Type::STRING);
    $generator = new Generator(GeneratorType::UUID);
    $validator = new Validator(ValidatorType::REQUIRED);
    $option = new Option;
    $option->setConst('admin');
    $option->setTitle('Admin');
    $attribute->addGenerator($generator);
    $attribute->addValidator($validator);
    $attribute->addOption($option);
    expect($attribute->getGenerators())->toHaveCount(1)
        ->and($attribute->getValidators())->toHaveCount(1)
        ->and($attribute->getOptions())->toHaveCount(1);
});

test('toArray serializes all fields and collections', function () {
    $attribute = new Attribute('alias', 'Alias', Type::ALIAS);
    $attribute->setPrimaryKey(true);
    $attribute->setLabelKey(true);
    $attribute->setDeleteKey(true);
    $attribute->setAliasSource('user.name');
    $attribute->setAliasTransformation('upper(user.name)');
    $attribute->setRelatedModelName('User');
    $attribute->setDependsOn('user_id');
    $attribute->setDependsOn('role_id');
    $generator = new Generator(GeneratorType::UUID);
    $validator = new Validator(ValidatorType::REQUIRED);
    $option = new Option;
    $option->setConst('admin');
    $option->setTitle('Admin');
    $attribute->addGenerator($generator);
    $attribute->addValidator($validator);
    $attribute->addOption($option);
    $arr = $attribute->toArray();
    expect($arr['name'])->toBe('alias')
        ->and($arr['label'])->toBe('Alias')
        ->and($arr['type'])->toBe('alias')
        ->and($arr['primaryKey'])->toBeTrue()
        ->and($arr['labelKey'])->toBeTrue()
        ->and($arr['deleteKey'])->toBeTrue()
        ->and($arr['source'])->toBe('user.name')
        ->and($arr['transform'])->toBe('upper(user.name)')
        ->and($arr['relatedModelName'])->toBe('User')
        ->and($arr['dependsOn'])->toBeArray()
        ->and($arr['dependsOn'])->toHaveCount(2)
        ->and($arr['dependsOn'])->toContain('user_id', 'role_id')
        ->and($arr['generators'])->toBeArray()
        ->and($arr['validators'])->toBeArray()
        ->and($arr['options'])->toBeArray();
});

test('can remove generator, validator, and option by id', function () {
    $attribute = new Attribute('username', 'Username', Type::STRING);
    $generator1 = new Generator(GeneratorType::UUID);
    $generator2 = new Generator(GeneratorType::UNIQUE_SLUG);
    $validator1 = new Validator(ValidatorType::REQUIRED);
    $validator2 = new Validator(ValidatorType::UNIQUE);
    $option1 = new Option;
    $option1->setConst('admin');
    $option1->setTitle('Admin');
    $option2 = new Option;
    $option2->setConst('user');
    $option2->setTitle('User');
    $attribute->addGenerator($generator1);
    $attribute->addGenerator($generator2);
    $attribute->addValidator($validator1);
    $attribute->addValidator($validator2);
    $attribute->addOption($option1);
    $attribute->addOption($option2);
    // dd($attribute->toArray());
    $attribute->removeGeneratorById($generator1->getId());
    $attribute->removeValidatorById($validator2->getId());
    $attribute->removeOptionById($option1->getId());
    expect($attribute->getGenerators())->toHaveCount(1)
        ->and($attribute->getGenerators()->first()->getId())->toBe($generator2->getId())
        ->and($attribute->getValidators())->toHaveCount(1)
        ->and($attribute->getValidators()->first()->getId())->toBe($validator1->getId())
        ->and($attribute->getOptions())->toHaveCount(1)
        ->and($attribute->getOptions()->first()->getId())->toBe($option2->getId());
});

test('can create attribute from array with new properties', function () {
    $data = [
        'name' => 'username',
        'label' => 'Username',
        'type' => 'string',
        'relatedModelName' => 'User',
        'dependsOn' => ['user_id', 'role_id'],
    ];

    $attribute = Attribute::fromArray('username', $data);

    expect($attribute->getName())->toBe('username')
        ->and($attribute->getLabel())->toBe('Username')
        ->and($attribute->getType())->toBe(Type::STRING)
        ->and($attribute->getRelatedModelName())->toBe('User')
        ->and($attribute->getDependsOn())->toHaveCount(2)
        ->and($attribute->getDependsOn()->toArray())->toContain('user_id', 'role_id');
});
