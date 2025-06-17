<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Model\Attributes\Option;
use LCSEngine\Schemas\Query\LensSimpleFilter\LensFilterType;
use LCSEngine\Schemas\Query\LensSimpleFilter\LensSimpleFilter;

uses()->group('query');

test('can create LensSimpleFilter instance and has correct initial state', function () {
    $filter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');

    expect($filter)->toBeInstanceOf(LensSimpleFilter::class);
    expect($filter->getName())->toBe('status');
    expect($filter->getLabel())->toBe('status'); // Default label is name
    expect($filter->getType())->toBe(LensFilterType::ENUM);
    expect($filter->getModel())->toBe('user');
    expect($filter->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($filter->getDependsOn())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can set and get label', function () {
    $filter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');
    $filter->setLabel('User Status');
    expect($filter->getLabel())->toBe('User Status');
});

test('can set and get type', function () {
    $filter = new LensSimpleFilter('dateFilter', LensFilterType::ENUM->value, 'user');
    $filter->setType(LensFilterType::DATE);
    expect($filter->getType())->toBe(LensFilterType::DATE);
});

test('can add and remove options', function () {
    $filter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');

    $option1 = new Option;
    $option1->setId('active');
    $option1->setConst('ACTIVE');
    $option1->setTitle('Active');

    $option2 = new Option;
    $option2->setId('pending');
    $option2->setConst('PENDING');
    $option2->setTitle('Pending');

    $filter->addOption($option1);
    $filter->addOption($option2);

    expect($filter->getOptions())->toHaveCount(2);
    expect($filter->getOptions()->first())->toBeInstanceOf(Option::class);
    expect($filter->getOptions()->map(fn ($o) => $o->getId())->toArray())->toEqual(['active', 'pending']);

    $filter->removeOption('active');
    expect($filter->getOptions())->toHaveCount(1);
    expect($filter->getOptions()->map(fn ($o) => $o->getId())->toArray())->toEqual(['pending']);
});

test('can add and remove dependencies', function () {
    $filter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');

    $filter->addDependsOn('department');
    $filter->addDependsOn('location');
    $filter->addDependsOn('department'); // Should not add duplicate

    expect($filter->getDependsOn())->toHaveCount(2);
    expect($filter->getDependsOn()->toArray())->toEqual(['department', 'location']);

    $filter->removeDependsOn('department');
    expect($filter->getDependsOn())->toHaveCount(1);
    expect($filter->getDependsOn()->toArray())->toEqual(['location']);
});

test('toArray method returns correct array structure', function () {
    $filter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');
    $filter->setLabel('User Status');

    $option1 = new Option;
    $option1->setId('active');
    $option1->setConst('ACTIVE');
    $option1->setTitle('Active');
    $filter->addOption($option1);

    $filter->addDependsOn('department');

    $expectedArray = [
        'type' => 'enum',
        'model' => 'user',
        'name' => 'status',
        'label' => 'User Status',
        'options' => [
            ['id' => 'active', 'const' => 'ACTIVE', 'title' => 'Active'],
        ],
        'dependsOn' => ['department'],
    ];

    expect($filter->toArray())->toEqual($expectedArray);
});

test('fromArray method creates LensSimpleFilter from array', function () {
    $filterData = [
        'type' => 'date',
        'model' => 'product',
        'name' => 'createdAt',
        'label' => 'Created At',
        'options' => [
            ['id' => 'today', 'const' => 'TODAY', 'title' => 'Today'],
        ],
        'dependsOn' => ['productType', 'vendor'],
    ];

    $filter = LensSimpleFilter::fromArray($filterData);

    expect($filter)->toBeInstanceOf(LensSimpleFilter::class);
    expect($filter->getName())->toBe('createdAt');
    expect($filter->getLabel())->toBe('Created At');
    expect($filter->getType())->toBe(LensFilterType::DATE);
    expect($filter->getModel())->toBe('product');
    expect($filter->getOptions())->toHaveCount(1);
    expect($filter->getOptions()->first()->getId())->toBe('today');
    expect($filter->getDependsOn())->toHaveCount(2);
    expect($filter->getDependsOn()->toArray())->toEqual(['productType', 'vendor']);
});
