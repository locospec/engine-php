<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\SectionItem;

uses()->group('query');

test('can create section item instance', function () {
    $section = new SectionItem('Personal Info');

    expect($section->getHeader())->toBe('Personal Info')
        ->and($section->getItems())->toBeInstanceOf(Collection::class)
        ->and($section->getItems())->toHaveCount(0);
});

test('can add items to section', function () {
    $section = new SectionItem('Personal Info');
    $field = new FieldItem('name');

    $section->addItem($field);
    expect($section->getItems())->toHaveCount(1);
});

test('can convert to array', function () {
    $section = new SectionItem('Personal Info');
    $section->addItem(new FieldItem('name'));
    $section->addItem(new FieldItem('email'));

    $array = $section->toArray();

    expect($array)->toBe([
        '$Personal Info',
        'name',
        'email',
    ]);
});
