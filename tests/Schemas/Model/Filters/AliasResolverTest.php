<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Type;
use LCSEngine\Schemas\Model\Filters\AliasResolver;
use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\Filters;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;

uses()->group('filters');

test('resolve aliases in condition', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'source' => 'users.name'
        ]),
        'user_email' => Attribute::fromArray('user_email', [
            'type' => 'alias',
            'source' => 'users.email'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $condition = Filters::condition('user_name', ComparisonOperator::IS, 'John');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('users.name')
        ->and($array['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['value'])->toBe('John');
});

test('resolve aliases in filter group', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'source' => 'users.name'
        ]),
        'user_email' => Attribute::fromArray('user_email', [
            'type' => 'alias',
            'source' => 'users.email'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $group = Filters::group(LogicalOperator::AND);
    $group->add(Filters::condition('user_name', ComparisonOperator::IS, 'John'))
        ->add(Filters::condition('user_email', ComparisonOperator::IS, 'john@example.com'));

    $filters = new Filters($group);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('users.name')
        ->and($array['conditions'][0]['value'])->toBe('John')
        ->and($array['conditions'][1]['attribute'])->toBe('users.email')
        ->and($array['conditions'][1]['value'])->toBe('john@example.com');
});

test('resolve aliases in nested groups', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'source' => 'users.name'
        ]),
        'user_email' => Attribute::fromArray('user_email', [
            'type' => 'alias',
            'source' => 'users.email'
        ]),
        'user_role' => Attribute::fromArray('user_role', [
            'type' => 'alias',
            'source' => 'users.role'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $orGroup = Filters::group(LogicalOperator::OR);
    $orGroup->add(Filters::condition('user_name', ComparisonOperator::IS, 'John'))
        ->add(Filters::condition('user_email', ComparisonOperator::IS, 'john@example.com'));

    $andGroup = Filters::group(LogicalOperator::AND);
    $andGroup->add($orGroup)
        ->add(Filters::condition('user_role', ComparisonOperator::IS, 'admin'));

    $filters = new Filters($andGroup);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['op'])->toBe(LogicalOperator::OR->value)
        ->and($array['conditions'][0]['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['conditions'][0]['attribute'])->toBe('users.name')
        ->and($array['conditions'][0]['conditions'][1]['attribute'])->toBe('users.email')
        ->and($array['conditions'][1]['attribute'])->toBe('users.role');
});

test('resolve aliases in primitive filter set', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'source' => 'users.name'
        ]),
        'user_email' => Attribute::fromArray('user_email', [
            'type' => 'alias',
            'source' => 'users.email'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $primitive = Filters::primitive();
    $primitive->add('user_name', 'John')
        ->add('user_email', 'john@example.com');

    $filters = new Filters($primitive);
    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['op'])->toBe(LogicalOperator::AND->value)
        ->and($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('users.name')
        ->and($array['conditions'][0]['value'])->toBe('John')
        ->and($array['conditions'][1]['attribute'])->toBe('users.email')
        ->and($array['conditions'][1]['value'])->toBe('john@example.com');
});

test('keep non-aliased attributes unchanged', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'source' => 'users.name'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $condition = Filters::condition('status', ComparisonOperator::IS, 'active');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('status')
        ->and($array['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['value'])->toBe('active');
});

test('handle aliases without source', function () {
    $aliases = collect([
        'user_name' => Attribute::fromArray('user_name', [
            'type' => 'alias',
            'transform' => 'uppercase'
        ]),
    ]);

    $resolver = new AliasResolver($aliases);

    $condition = Filters::condition('user_name', ComparisonOperator::IS, 'John');
    $filters = new Filters($condition);

    $resolved = $resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('user_name')
        ->and($array['op'])->toBe(ComparisonOperator::IS->value)
        ->and($array['value'])->toBe('John');
});
