<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\Condition;
use LCSEngine\Schemas\Model\Filters\Filters;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;

uses()->group('filters');

test('filters with condition root', function () {
    $group = Filters::group(LogicalOperator::AND);
    $condition = Filters::condition('name', ComparisonOperator::IS, 'John');
    $filters = new Filters($condition);

    // dump(["Log"=>"1", "condition" => $condition, "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($filters->getRoot())->toBe($condition)
        ->and($filters->toArray())->toHaveKeys(['attribute', 'op', 'value'])
        ->and($filters->toArray()['attribute'])->toBe('name')
        ->and($filters->toArray()['op'])->toBe(ComparisonOperator::IS->value)
        ->and($filters->toArray()['value'])->toBe('John');
});

test('filters with group root', function () {
    $group = Filters::group(LogicalOperator::AND);
    $group->add(Filters::condition('status', ComparisonOperator::IS, 'active'))
        ->add(Filters::condition('age', ComparisonOperator::GREATER_THAN, 18));

    $filters = new Filters($group);

    // dump(["Log"=>"2", "group" => $group, "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($filters->getRoot())->toBe($group)
        ->and($filters->toArray())->toHaveKeys(['op', 'conditions'])
        ->and($filters->toArray()['op'])->toBe(LogicalOperator::AND->value)
        ->and($filters->toArray()['conditions'])->toHaveCount(2);
});

test('filters with primitive root', function () {
    $primitive = Filters::primitive();
    $primitive->add('status', 'active')
        ->add('age', 18);

    $filters = new Filters($primitive);

    // dump(["Log"=>"3", "primitive" => $primitive, "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($filters->getRoot())->toBe($primitive)
        ->and($filters->toArray())->toHaveKeys(['op', 'conditions'])
        ->and($filters->toArray()['op'])->toBe(LogicalOperator::AND->value)
        ->and($filters->toArray()['conditions'])->toHaveCount(2)
        ->and($filters->toArray()['conditions'][0]['attribute'])->toBe('status')
        ->and($filters->toArray()['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($filters->toArray()['conditions'][0]['value'])->toBe('active')
        ->and($filters->toArray()['conditions'][1]['attribute'])->toBe('age')
        ->and($filters->toArray()['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($filters->toArray()['conditions'][1]['value'])->toBe(18);
});

test('complex filter structure', function () {
    $group = Filters::group(LogicalOperator::AND);

    $orGroup = Filters::group(LogicalOperator::OR);
    $orGroup->add(Filters::condition('status', ComparisonOperator::IS, 'active'))
        ->add(Filters::condition('status', ComparisonOperator::IS, 'pending'));

    $group->add($orGroup)
        ->add(Filters::condition('age', ComparisonOperator::GREATER_THAN, 18));

    $filters = new Filters($group);

    // dump(["Log"=>"4", "group" => $group, "filters" => $filters, "filters array"=> $filters->toArray()]);

    $array = $filters->toArray();
    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['op'])->toBe(LogicalOperator::OR->value)
        ->and($array['conditions'][0]['conditions'])->toHaveCount(2)
        ->and($array['conditions'][1]['attribute'])->toBe('age');
});

test('create filters from array - case 1: full structure', function () {
    $data = [
        'op' => 'and',
        'conditions' => [
            [
                'op' => 'or',
                'conditions' => [
                    [
                        'attribute' => 'status',
                        'op' => 'is',
                        'value' => 'active',
                    ],
                    [
                        'attribute' => 'status',
                        'op' => 'is',
                        'value' => 'pending',
                    ],
                ],
            ],
            [
                'attribute' => 'age',
                'op' => 'greater_than',
                'value' => 18,
            ],
        ],
    ];

    $filters = Filters::fromArray($data);
    $array = $filters->toArray();
    // dump(["Log"=>"5", "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['op'])->toBe(LogicalOperator::OR->value)
        ->and($array['conditions'][0]['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['conditions'][0]['attribute'])->toBe('status')
        ->and($array['conditions'][0]['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['conditions'][0]['value'])->toBe('active')
        ->and($array['conditions'][0]['conditions'][1]['attribute'])->toBe('status')
        ->and($array['conditions'][0]['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['conditions'][1]['value'])->toBe('pending')
        ->and($array['conditions'][1]['attribute'])->toBe('age')
        ->and($array['conditions'][1]['op'])->toBe(ComparisonOperator::GREATER_THAN->value)
        ->and($array['conditions'][1]['value'])->toBe(18);
});

test('create filters from array - case 2: array of conditions', function () {
    $data = [
        [
            'attribute' => 'status',
            'op' => 'is',
            'value' => 'active',
        ],
        [
            'attribute' => 'age',
            'op' => 'is',
            'value' => 18,
        ],
    ];

    $filters = Filters::fromArray($data);
    $array = $filters->toArray();
    // dump(["Log"=>"6", "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('status')
        ->and($array['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['value'])->toBe('active')
        ->and($array['conditions'][1]['attribute'])->toBe('age')
        ->and($array['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][1]['value'])->toBe(18);
});

test('create filters from array - case 3: simple key-value pairs', function () {
    $data = [
        'status' => 'active',
        'age' => 18,
    ];

    $filters = Filters::fromArray($data);
    $array = $filters->toArray();
    // dump(["Log"=>"7", "filters" => $filters, "filters array"=> $filters->toArray()]);

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('status')
        ->and($array['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['value'])->toBe('active')
        ->and($array['conditions'][1]['attribute'])->toBe('age')
        ->and($array['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][1]['value'])->toBe(18);
});
