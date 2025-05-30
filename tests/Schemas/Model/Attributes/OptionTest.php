<?php

namespace LCSEngine\Tests\Schemas\Model;

use LCSEngine\Schemas\Model\Attributes\Option;

test('option basic creation', function () {
    $option = new Option;
    $option->setTitle('Active')
        ->setConst('active');

    expect($option->getTitle())->toBe('Active')
        ->and($option->getConst())->toBe('active');
});

test('option to array', function () {
    $option = new Option;
    $option->setTitle('Inactive')
        ->setConst('inactive');

    $array = $option->toArray();
    expect($array)->toHaveKeys(['title', 'const'])
        ->and($array['title'])->toBe('Inactive')
        ->and($array['const'])->toBe('inactive');
});

test('option with special characters', function () {
    $option = new Option;
    $option->setTitle('Not Started')
        ->setConst('not_started');

    expect($option->getTitle())->toBe('Not Started')
        ->and($option->getConst())->toBe('not_started')
        ->and($option->toArray())->toHaveKeys(['title', 'const'])
        ->and($option->toArray()['title'])->toBe('Not Started')
        ->and($option->toArray()['const'])->toBe('not_started');
});
