<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\ContextResolver;
use LCSEngine\Schemas\Model\Filters\Filters;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;

uses()->group('filters');

test('resolve context in condition value', function () {
    $context = [
        'search' => 'resi',
        'status' => 'active',
        'age' => 18,
    ];

    $resolver = new ContextResolver($context);

    $condition = Filters::condition('name', ComparisonOperator::CONTAINS, '$.search');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['value'])->toBe('resi');
});

test('resolve context in filter group', function () {
    $context = [
        'search' => 'resi',
        'status' => 'active',
        'age' => 18,
    ];

    $resolver = new ContextResolver($context);

    $group = Filters::group(LogicalOperator::AND);
    $group->add(Filters::condition('name', ComparisonOperator::CONTAINS, '$.search'))
        ->add(Filters::condition('status', ComparisonOperator::IS, '$.status'))
        ->add(Filters::condition('age', ComparisonOperator::GREATER_THAN, '$.age'));

    $filters = new Filters($group);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['conditions'])->toHaveCount(3)
        ->and($array['conditions'][0]['value'])->toBe('resi')
        ->and($array['conditions'][1]['value'])->toBe('active')
        ->and($array['conditions'][2]['value'])->toBe(18);
});

test('resolve context in nested groups', function () {
    $context = [
        'search' => 'resi',
        'status' => 'active',
    ];

    $resolver = new ContextResolver($context);

    $orGroup = Filters::group(LogicalOperator::OR);
    $orGroup->add(Filters::condition('name', ComparisonOperator::CONTAINS, '$.search'))
        ->add(Filters::condition('status', ComparisonOperator::IS, '$.status'));

    $andGroup = Filters::group(LogicalOperator::AND);
    $andGroup->add($orGroup)
        ->add(Filters::condition('type', ComparisonOperator::IS, 'user'));

    $filters = new Filters($andGroup);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['conditions'][0]['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['conditions'][0]['value'])->toBe('resi')
        ->and($array['conditions'][0]['conditions'][1]['value'])->toBe('active')
        ->and($array['conditions'][1]['value'])->toBe('user');
});

test('resolve context in primitive filter set', function () {
    $context = [
        'search' => 'resi',
        'status' => 'active',
    ];

    $resolver = new ContextResolver($context);

    $primitive = Filters::primitive();
    $primitive->add('name', '$.search')
        ->add('status', '$.status');

    $filters = new Filters($primitive);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['value'])->toBe('resi')
        ->and($array['conditions'][1]['value'])->toBe('active');
});

test('keep non-context values unchanged', function () {
    $context = [
        'search' => 'resi',
    ];

    $resolver = new ContextResolver($context);

    $condition = Filters::condition('status', ComparisonOperator::IS, 'active');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['value'])->toBe('active');
});

test('handle missing context values', function () {
    $context = [
        'search' => 'resi',
    ];

    $resolver = new ContextResolver($context);

    $condition = Filters::condition('status', ComparisonOperator::IS, '$.missing');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['value'])->toBe('$.missing');
});

test('resolve context in array values', function () {
    $context = [
        'status' => 'active',
        'other_status' => 'pending',
    ];

    $resolver = new ContextResolver($context);

    $condition = Filters::condition('status', ComparisonOperator::IS_ANY_OF, ['$.status', '$.other_status']);
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['value'])->toBe(['active', 'pending']);
});
