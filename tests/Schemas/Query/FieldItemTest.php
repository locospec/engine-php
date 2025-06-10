<?php

namespace LCSEngine\Tests\Schemas\Query;

use LCSEngine\Schemas\Query\FieldItem;

uses()->group('query');

test('can create FieldItem instance', function () {
    $item = new FieldItem('name');

    expect($item)->toBeInstanceOf(FieldItem::class);
    expect($item->getField())->toBe('name');
});

test('can convert to array', function () {
    $item = new FieldItem('name');
    $array = $item->toArray();

    expect($array)->toBe(['name']);
});
