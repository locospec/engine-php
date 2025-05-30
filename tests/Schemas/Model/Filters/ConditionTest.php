<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\Condition;

uses()->group('filters');

test('condition basic creation', function () {
    $condition = new Condition('name', ComparisonOperator::IS, 'John');

    expect($condition->getAttribute())->toBe('name')
        ->and($condition->getOperator())->toBe(ComparisonOperator::IS)
        ->and($condition->getValue())->toBe('John');
});

test('condition with different operators', function () {
    $conditions = [
        new Condition('age', ComparisonOperator::GREATER_THAN, 18),
        new Condition('status', ComparisonOperator::IS_NOT, 'active'),
        new Condition('tags', ComparisonOperator::CONTAINS, 'important'),
    ];

    expect($conditions[0]->getOperator())->toBe(ComparisonOperator::GREATER_THAN)
        ->and($conditions[1]->getOperator())->toBe(ComparisonOperator::IS_NOT)
        ->and($conditions[2]->getOperator())->toBe(ComparisonOperator::CONTAINS);
});

test('condition to array', function () {
    $condition = new Condition('price', ComparisonOperator::LESS_THAN, 100.50);

    $array = $condition->toArray();
    expect($array)->toHaveKeys(['attribute', 'op', 'value'])
        ->and($array['attribute'])->toBe('price')
        ->and($array['op'])->toBe(ComparisonOperator::LESS_THAN->value)
        ->and($array['value'])->toBe(100.50);
});

test('condition with array value', function () {
    $condition = new Condition('status', ComparisonOperator::IS_ANY_OF, ['active', 'pending']);

    expect($condition->getValue())->toBe(['active', 'pending'])
        ->and($condition->toArray()['value'])->toBe(['active', 'pending']);
});
