<?php

namespace LCSEngine\Tests\Schemas\Query;

use LCSEngine\Schemas\Query\ActionOption;

uses()->group('query');

test('can create ActionOption instance', function () {
    $option = new ActionOption('quick', 'Quick Edit', '/quick-edit');

    expect($option)->toBeInstanceOf(ActionOption::class);
    expect($option->getKey())->toBe('quick');
    expect($option->getLabel())->toBe('Quick Edit');
    expect($option->getUrl())->toBe('/quick-edit');
});

test('can create ActionOption from array', function () {
    $data = [
        'key' => 'quick',
        'label' => 'Quick Edit',
        'url' => '/quick-edit',
    ];

    $option = ActionOption::fromArray($data);

    expect($option)->toBeInstanceOf(ActionOption::class);
    expect($option->getKey())->toBe('quick');
    expect($option->getLabel())->toBe('Quick Edit');
    expect($option->getUrl())->toBe('/quick-edit');
});

test('can create action option instance', function () {
    $option = new ActionOption('delete', 'Delete', '/delete/{id}');

    expect($option->getKey())->toBe('delete')
        ->and($option->getLabel())->toBe('Delete')
        ->and($option->getUrl())->toBe('/delete/{id}');
});

test('can create from array', function () {
    $data = [
        'key' => 'delete',
        'label' => 'Delete',
        'url' => '/delete/{id}'
    ];

    $option = ActionOption::fromArray($data);

    expect($option->getKey())->toBe('delete')
        ->and($option->getLabel())->toBe('Delete')
        ->and($option->getUrl())->toBe('/delete/{id}');
});

test('can convert to array', function () {
    $option = new ActionOption('delete', 'Delete', '/delete/{id}');
    $array = $option->toArray();

    expect($array)->toBe([
        'key' => 'delete',
        'label' => 'Delete',
        'url' => '/delete/{id}'
    ]);
});
