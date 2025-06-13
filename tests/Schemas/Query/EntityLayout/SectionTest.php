<?php

namespace LCSEngine\Tests\Schemas\Query\EntityLayout;

use LCSEngine\Schemas\Query\EntityLayout\Section;
use LCSEngine\Schemas\Query\EntityLayout\Field;

uses()->group('query');

test('can add fields to a section', function () {
    $section = new Section('Basic Info');
    $section->addField(new Field('title', 'Title'));
    $section->addField(new Field('desc', 'Description'));

    expect($section->getFields())->toHaveCount(2);
    expect($section->getLabel())->toBe('Basic Info');
});

test('can nest sections in a section', function () {
    $inner = (new Section('Inner'))
        ->addField(new Field('key1', 'Key 1'));

    $outer = (new Section('Outer'))
        ->addSection($inner)
        ->addField(new Field('key2', 'Key 2'));

    $array = $outer->toArray();

    expect($array)->toMatchArray([
        'section' => 'Outer',
        'fields' => [
            [
                'section' => 'Inner',
                'fields' => [
                    ['key' => 'key1', 'label' => 'Key 1', 'type' => 'string']
                ]
            ],
            ['key' => 'key2', 'label' => 'Key 2', 'type' => 'string']
        ]
    ]);
});

test('can convert section to and from array', function () {
    $data = [
        'section' => 'Outer',
        'fields' => [
            ['key' => 'k1', 'label' => 'K1', 'type' => 'string'],
            [
                'section' => 'Inner',
                'fields' => [
                    ['key' => 'k2', 'label' => 'K2', 'type' => 'string']
                ]
            ]
        ]
    ];

    $section = Section::fromArray($data);

    expect($section->toArray())->toBe($data);
});
