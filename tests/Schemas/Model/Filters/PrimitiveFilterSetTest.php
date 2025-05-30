<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;
use LCSEngine\Schemas\Model\Filters\PrimitiveFilterSet;

uses()->group('filters');

test('primitive filter set basic creation', function () {
    $filterSet = new PrimitiveFilterSet();

    expect($filterSet->getFilters())->toBeEmpty()
        ->and($filterSet->toArray())->toBeEmpty();
});

test('primitive filter set with filters', function () {
    $filterSet = new PrimitiveFilterSet();
    
    $filterSet->add('status', 'active')
        ->add('age', 18)
        ->add('name', 'John');

    expect($filterSet->getFilters())->toHaveCount(3)
        ->and($filterSet->getFilters()['status'])->toBe('active')
        ->and($filterSet->getFilters()['age'])->toBe(18)
        ->and($filterSet->getFilters()['name'])->toBe('John');
});

test('primitive filter set to array', function () {
    $filterSet = new PrimitiveFilterSet();
    
    $filterSet->add('status', 'active')
        ->add('age', 18);

    $array = $filterSet->toArray();
    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('status')
        ->and($array['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['value'])->toBe('active')
        ->and($array['conditions'][1]['attribute'])->toBe('age')
        ->and($array['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][1]['value'])->toBe(18);
});

test('primitive filter set with array values', function () {
    $filterSet = new PrimitiveFilterSet();
    
    $filterSet->add('tags', ['important', 'urgent'])
        ->add('settings', ['notifications' => true, 'theme' => 'dark']);

    $array = $filterSet->toArray();
    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('tags')
        ->and($array['conditions'][0]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][0]['value'])->toBe(['important', 'urgent'])
        ->and($array['conditions'][1]['attribute'])->toBe('settings')
        ->and($array['conditions'][1]['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['conditions'][1]['value'])->toBe(['notifications' => true, 'theme' => 'dark']);
}); 