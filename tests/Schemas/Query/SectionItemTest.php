<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ColumnItem;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\SectionItem;

uses()->group('query');

test('can create section item instance', function () {
    $section = new SectionItem('Personal Info');

    expect($section->getHeader())->toBe('Personal Info')
        ->and($section->getColumns())->toBeInstanceOf(Collection::class)
        ->and($section->getColumns())->toHaveCount(0);
});

test('can add columns to section', function () {
    $section = new SectionItem('Personal Info');
    $column = new ColumnItem('Basic Info');
    $column->addItem(new FieldItem('name'));
    $section->addColumn($column);

    expect($section->getColumns())->toHaveCount(1);
    expect($section->getColumns()->first())->toBeInstanceOf(ColumnItem::class);
});

test('can convert to array', function () {
    $section = new SectionItem('Personal Info');

    $column1 = new ColumnItem('Basic Info');
    $column1->addItem(new FieldItem('name'));
    $column1->addItem(new FieldItem('email'));
    $section->addColumn($column1);

    $column2 = new ColumnItem('Contact Info');
    $column2->addItem(new FieldItem('phone'));
    $section->addColumn($column2);

    $array = $section->toArray();

    expect($array)->toEqual([
        '$Personal Info',
        ['@Basic Info', 'name', 'email'],
        ['@Contact Info', 'phone'],
    ]);
});
