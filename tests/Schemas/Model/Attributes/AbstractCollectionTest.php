<?php

namespace LCSEngine\Tests\Schemas\Model;

use LCSEngine\Schemas\Model\Attributes\Generator;
use LCSEngine\Schemas\Model\Attributes\Generators;
use LCSEngine\Schemas\Model\Attributes\GeneratorType;
use LCSEngine\Schemas\Model\Attributes\OperationType;

// test('collection basic operations', function () {
//     $collection = new Generators();

//     expect($collection->isEmpty())->toBeTrue()
//         ->and($collection->count())->toBe(0);

//     $generator = new Generator();
//     $generator->setType(GeneratorType::UUID_GENERATOR)
//         ->setOperations([OperationType::INSERT->value]);

//     $collection->add($generator);

//     expect($collection->isEmpty())->toBeFalse()
//         ->and($collection->count())->toBe(1)
//         ->and($collection->getAll())->toHaveCount(1)
//         ->and($collection->getAll()[0])->toBe($generator);
// });

// test('collection remove operation', function () {
//     $collection = new Generators();

//     $generator = new Generator();
//     $generator->setType(GeneratorType::UUID_GENERATOR)
//         ->setOperations([OperationType::INSERT->value]);

//     $collection->add($generator);
//     $id = $generator->getId();

//     expect($collection->count())->toBe(1);

//     $collection->remove($id);

//     expect($collection->isEmpty())->toBeTrue()
//         ->and($collection->count())->toBe(0);
// });

// test('collection with multiple items', function () {
//     $collection = new Generators();

//     $generator1 = new Generator();
//     $generator1->setType(GeneratorType::UUID_GENERATOR)
//         ->setOperations([OperationType::INSERT->value]);

//     $generator2 = new Generator();
//     $generator2->setType(GeneratorType::TIMESTAMP_GENERATOR)
//         ->setOperations([OperationType::INSERT->value]);

//     $collection->add($generator1);
//     $collection->add($generator2);

//     expect($collection->count())->toBe(2)
//         ->and($collection->getAll())->toHaveCount(2)
//         ->and($collection->getAll()[0])->toBe($generator1)
//         ->and($collection->getAll()[1])->toBe($generator2);
// });
