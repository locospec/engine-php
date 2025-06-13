<?php

namespace LCSEngine\Tests\Schemas\Query\EntityLayout;

use LCSEngine\Schemas\Query\EntityLayout\Field;

uses()->group('query');

test('can create a field and convert to array', function () {
    $field = new Field('id', 'ID');

    expect($field->toArray())->toBe([
        'key' => 'id',
        'label' => 'ID',
        'type' => 'string',
    ]);
});

test('can instantiate a field from array', function () {
    $data = ['key' => 'slug', 'label' => 'Slug', 'type' => 'string'];
    $field = Field::fromArray($data);

    expect($field->toArray())->toBe($data);
});
