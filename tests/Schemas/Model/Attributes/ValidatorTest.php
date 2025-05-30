<?php

namespace LCSEngine\Tests\Schemas\Model;

use LCSEngine\Schemas\Model\Attributes\Validator;
use LCSEngine\Schemas\Model\Attributes\ValidatorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;

test('validator basic creation', function () {
    $validator = new Validator();
    $validator->setType(ValidatorType::REQUIRED)
        ->setOperations([OperationType::INSERT->value])
        ->setMessage('Field is required');

    expect($validator->getType())->toBe(ValidatorType::REQUIRED)
        ->and($validator->getOperations())->toBe([OperationType::INSERT->value])
        ->and($validator->getMessage())->toBe('Field is required');
});

test('validator with multiple operations', function () {
    $validator = new Validator();
    $validator->setType(ValidatorType::UNIQUE)
        ->setOperations([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->setMessage('Value must be unique');

    expect($validator->getType())->toBe(ValidatorType::UNIQUE)
        ->and($validator->getOperations())->toBe([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->and($validator->getMessage())->toBe('Value must be unique');
});

test('validator to array', function () {
    $validator = new Validator();
    $validator->setType(ValidatorType::REQUIRED)
        ->setOperations([OperationType::INSERT->value])
        ->setMessage('Field is required');

    $array = $validator->toArray();
    expect($array)->toHaveKeys(['type', 'operations', 'message'])
        ->and($array['type'])->toBe(ValidatorType::REQUIRED->value)
        ->and($array['operations'])->toBe([OperationType::INSERT->value])
        ->and($array['message'])->toBe('Field is required');
});

test('validator with invalid operation throws exception', function () {
    $validator = new Validator();
    
    expect(fn() => $validator->setOperations(['invalid_operation']))
        ->toThrow(\InvalidArgumentException::class, 'Invalid operation type: invalid_operation');
}); 