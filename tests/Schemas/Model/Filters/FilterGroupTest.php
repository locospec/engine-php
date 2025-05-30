<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\Condition;
use LCSEngine\Schemas\Model\Filters\FilterGroup;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;

uses()->group('filters');

test('filter group basic creation', function () {
    $group = new FilterGroup(LogicalOperator::AND);

    expect($group->getOperator())->toBe(LogicalOperator::AND)
        ->and($group->getConditions())->toBeEmpty();
});

test('filter group with conditions', function () {
    $group = new FilterGroup(LogicalOperator::OR);
    
    $condition1 = new Condition('status', ComparisonOperator::IS, 'active');
    $condition2 = new Condition('age', ComparisonOperator::GREATER_THAN, 18);

    $group->add($condition1)
        ->add($condition2);

    expect($group->getConditions())->toHaveCount(2)
        ->and($group->getConditions()[0])->toBe($condition1)
        ->and($group->getConditions()[1])->toBe($condition2);
});

test('filter group to array', function () {
    $group = new FilterGroup(LogicalOperator::AND);
    
    $condition1 = new Condition('name', ComparisonOperator::CONTAINS, 'John');
    $condition2 = new Condition('age', ComparisonOperator::GREATER_THAN, 18);

    $group->add($condition1)
        ->add($condition2);

    $array = $group->toArray();
    expect($array)->toHaveKeys(['op', 'conditions'])
        ->and($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('name')
        ->and($array['conditions'][1]['attribute'])->toBe('age');
});

test('nested filter groups', function () {
    $outerGroup = new FilterGroup(LogicalOperator::AND);
    $innerGroup = new FilterGroup(LogicalOperator::OR);

    $condition1 = new Condition('status', ComparisonOperator::IS, 'active');
    $condition2 = new Condition('age', ComparisonOperator::GREATER_THAN, 18);

    $innerGroup->add($condition1)
        ->add($condition2);

    $outerGroup->add($innerGroup);

    $array = $outerGroup->toArray();
    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(1)
        ->and($array['conditions'][0]['op'])->toBe(LogicalOperator::OR->value)
        ->and($array['conditions'][0]['conditions'])->toHaveCount(2);
}); 