<?php

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Model\Attributes\Generator;
use LCSEngine\Schemas\Model\Attributes\GeneratorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;

uses()->group('attributes');

test('can create and get/set all fields', function () {
    $generator = new Generator(GeneratorType::UUID);
    $generator->setId('gen1');
    $generator->setSource('user.id');
    $generator->setValue('some-value');
    $generator->addOperation(OperationType::INSERT);
    expect($generator->getId())->toBe('gen1')
        ->and($generator->getType())->toBe(GeneratorType::UUID)
        ->and($generator->getSource())->toBe('user.id')
        ->and($generator->getValue())->toBe('some-value')
        ->and($generator->getOperations())->toBeInstanceOf(Collection::class)
        ->and($generator->getOperations()->first())->toBe(OperationType::INSERT);
});

test('can add and remove operations', function () {
    $generator = new Generator(GeneratorType::UUID);
    $generator->addOperation(OperationType::INSERT);
    $generator->addOperation(OperationType::UPDATE);
    expect($generator->getOperations()->count())->toBe(2)
        ->and($generator->getOperations()->contains(OperationType::INSERT))->toBeTrue()
        ->and($generator->getOperations()->contains(OperationType::UPDATE))->toBeTrue();

    $generator->removeOperation(OperationType::INSERT);
    expect($generator->getOperations()->count())->toBe(1)
        ->and($generator->getOperations()->contains(OperationType::INSERT))->toBeFalse()
        ->and($generator->getOperations()->contains(OperationType::UPDATE))->toBeTrue();
});

test('toArray serializes all fields', function () {
    $generator = new Generator(GeneratorType::SLUG_GENERATOR);
    $generator->setId('gen2');
    $generator->setSource('user.slug');
    $generator->setValue('slug-value');
    $generator->addOperation(OperationType::UPDATE);
    $arr = $generator->toArray();
    expect($arr['id'])->toBe('gen2')
        ->and($arr['type'])->toBe('slug_generator')
        ->and($arr['source'])->toBe('user.slug')
        ->and($arr['value'])->toBe('slug-value')
        ->and($arr['operations'])->toBe(['update']);
});

test('fromArray creates generator from array', function () {
    $data = [
        'id' => 'gen3',
        'type' => 'timestamp_generator',
        'source' => 'created_at',
        'value' => 'now',
        'operations' => ['insert', 'update'],
    ];
    $generator = Generator::fromArray($data);
    expect($generator)->toBeInstanceOf(Generator::class)
        ->and($generator->getType())->toBe(GeneratorType::TIMESTAMP_GENERATOR)
        ->and($generator->getSource())->toBe('created_at')
        ->and($generator->getValue())->toBe('now')
        ->and($generator->getOperations())->toBeInstanceOf(Collection::class)
        ->and($generator->getOperations()->count())->toBe(2)
        ->and($generator->getOperations()->contains(OperationType::INSERT))->toBeTrue()
        ->and($generator->getOperations()->contains(OperationType::UPDATE))->toBeTrue();
});
