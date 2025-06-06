<?php

use LCSEngine\Schemas\Model\Attributes\Option;

uses()->group('attributes');

test('can create and get/set all fields', function () {
    $option = new Option;
    $option->setId('opt1');
    $option->setConst('admin');
    $option->setTitle('Admin');
    expect($option->getId())->toBe('opt1')
        ->and($option->getConst())->toBe('admin')
        ->and($option->getTitle())->toBe('Admin');
});

test('toArray serializes all fields', function () {
    $option = new Option;
    $option->setId('opt2');
    $option->setConst('user');
    $option->setTitle('User');
    $arr = $option->toArray();
    expect($arr['id'])->toBe('opt2')
        ->and($arr['const'])->toBe('user')
        ->and($arr['title'])->toBe('User');
});

test('fromArray creates option from array', function () {
    $data = [
        'id' => 'opt3',
        'const' => 'guest',
        'title' => 'Guest',
    ];
    $option = Option::fromArray($data);
    expect($option)->toBeInstanceOf(Option::class)
        ->and($option->getConst())->toBe('guest')
        ->and($option->getTitle())->toBe('Guest');
});
