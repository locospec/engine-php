<?php

use LCSEngine\Schemas\Model\Attributes\Validator;
use LCSEngine\Schemas\Model\Attributes\ValidatorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;
use Illuminate\Support\Collection;

uses()->group('attributes');

test('can create and get/set all fields', function () {
    $validator = new Validator(ValidatorType::REQUIRED);
    $validator->setId('val1');
    $validator->setMessage('Required!');
    $validator->addOperation(OperationType::INSERT);
    expect($validator->getId())->toBe('val1')
        ->and($validator->getType())->toBe(ValidatorType::REQUIRED)
        ->and($validator->getMessage())->toBe('Required!')
        ->and($validator->getOperations())->toBeInstanceOf(Collection::class)
        ->and($validator->getOperations()->first())->toBe(OperationType::INSERT);
});

test('can add and remove operations', function () {
    $validator = new Validator(ValidatorType::REQUIRED);
    $validator->addOperation(OperationType::INSERT);
    $validator->addOperation(OperationType::UPDATE);
    expect($validator->getOperations()->count())->toBe(2)
        ->and($validator->getOperations()->contains(OperationType::INSERT))->toBeTrue()
        ->and($validator->getOperations()->contains(OperationType::UPDATE))->toBeTrue();
    
    $validator->removeOperation(OperationType::INSERT);
    expect($validator->getOperations()->count())->toBe(1)
        ->and($validator->getOperations()->contains(OperationType::INSERT))->toBeFalse()
        ->and($validator->getOperations()->contains(OperationType::UPDATE))->toBeTrue();
});

test('toArray serializes all fields', function () {
    $validator = new Validator(ValidatorType::UNIQUE);
    $validator->setId('val2');
    $validator->setMessage('Must be unique!');
    $validator->addOperation(OperationType::UPDATE);
    $arr = $validator->toArray();
    expect($arr['id'])->toBe('val2')
        ->and($arr['type'])->toBe('unique')
        ->and($arr['message'])->toBe('Must be unique!')
        ->and($arr['operations'])->toBe(['update']);
});

test('fromArray creates validator from array', function () {
    $data = [
        'id' => 'val3',
        'type' => 'required',
        'message' => 'Required!',
        'operations' => ['insert', 'delete'],
    ];
    $validator = Validator::fromArray($data);
    expect($validator)->toBeInstanceOf(Validator::class)
        ->and($validator->getType())->toBe(ValidatorType::REQUIRED)
        ->and($validator->getMessage())->toBe('Required!')
        ->and($validator->getOperations())->toBeInstanceOf(Collection::class)
        ->and($validator->getOperations()->count())->toBe(2)
        ->and($validator->getOperations()->contains(OperationType::INSERT))->toBeTrue()
        ->and($validator->getOperations()->contains(OperationType::DELETE))->toBeTrue();
});