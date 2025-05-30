<?php

namespace LCSEngine\Tests\Schemas\Model;

use LCSEngine\Schemas\Model\Attributes\Generator;
use LCSEngine\Schemas\Model\Attributes\GeneratorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;

test('generator basic creation', function () {
    $generator = new Generator();
    $generator->setType(GeneratorType::UUID_GENERATOR)
        ->setOperations([OperationType::INSERT->value]);

    expect($generator->getType())->toBe(GeneratorType::UUID_GENERATOR)
        ->and($generator->getOperations())->toBe([OperationType::INSERT->value])
        ->and($generator->getSource())->toBeNull()
        ->and($generator->getValue())->toBeNull();
});

test('generator with source and value', function () {
    $generator = new Generator();
    $generator->setType(GeneratorType::SLUG_GENERATOR)
        ->setOperations([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->setSource('title')
        ->setValue('default-slug');

    expect($generator->getType())->toBe(GeneratorType::SLUG_GENERATOR)
        ->and($generator->getOperations())->toBe([OperationType::INSERT->value, OperationType::UPDATE->value])
        ->and($generator->getSource())->toBe('title')
        ->and($generator->getValue())->toBe('default-slug');
});

test('generator to array', function () {
    $generator = new Generator();
    $generator->setType(GeneratorType::TIMESTAMP_GENERATOR)
        ->setOperations([OperationType::INSERT->value])
        ->setSource('created_at');

    $array = $generator->toArray();
    expect($array)->toHaveKeys(['type', 'operations', 'source'])
        ->and($array['type'])->toBe(GeneratorType::TIMESTAMP_GENERATOR->value)
        ->and($array['operations'])->toBe([OperationType::INSERT->value])
        ->and($array['source'])->toBe('created_at');
});

test('generator with invalid operation throws exception', function () {
    $generator = new Generator();
    
    expect(fn() => $generator->setOperations(['invalid_operation']))
        ->toThrow(\InvalidArgumentException::class, 'Invalid operation type: invalid_operation');
}); 