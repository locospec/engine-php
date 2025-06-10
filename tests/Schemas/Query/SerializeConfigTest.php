<?php

namespace LCSEngine\Tests\Schemas\Query;

use LCSEngine\Schemas\Query\AlignType;
use LCSEngine\Schemas\Query\SerializeConfig;

uses()->group('query');

test('can create SerializeConfig instance with header and default alignment', function () {
    $config = new SerializeConfig('#');

    expect($config)->toBeInstanceOf(SerializeConfig::class);
    expect($config->getHeader())->toBe('#');
    expect($config->getAlign())->toBe(AlignType::LEFT);
});

test('can create SerializeConfig instance with custom alignment', function () {
    $config = new SerializeConfig('#', AlignType::RIGHT);

    expect($config)->toBeInstanceOf(SerializeConfig::class);
    expect($config->getHeader())->toBe('#');
    expect($config->getAlign())->toBe(AlignType::RIGHT);
});

test('can update header and alignment', function () {
    $config = new SerializeConfig('#');

    $config->setHeader('No.');
    expect($config->getHeader())->toBe('No.');

    $config->setAlign(AlignType::CENTER);
    expect($config->getAlign())->toBe(AlignType::CENTER);
});

test('can create SerializeConfig from array with all properties', function () {
    $data = [
        'header' => '#',
        'align' => 'right',
    ];

    $config = SerializeConfig::fromArray($data);

    expect($config)->toBeInstanceOf(SerializeConfig::class);
    expect($config->getHeader())->toBe('#');
    expect($config->getAlign())->toBe(AlignType::RIGHT);
});

test('can create SerializeConfig from array without alignment', function () {
    $data = [
        'header' => '#',
    ];

    $config = SerializeConfig::fromArray($data);

    expect($config)->toBeInstanceOf(SerializeConfig::class);
    expect($config->getHeader())->toBe('#');
    expect($config->getAlign())->toBe(AlignType::LEFT);
});

test('can create serialize config with default alignment', function () {
    $config = new SerializeConfig('Serial');

    expect($config->getHeader())->toBe('Serial')
        ->and($config->getAlign())->toBe(AlignType::LEFT);
});

test('can create serialize config with custom alignment', function () {
    $config = new SerializeConfig('Serial', AlignType::CENTER);

    expect($config->getHeader())->toBe('Serial')
        ->and($config->getAlign())->toBe(AlignType::CENTER);
});

test('can create from array with default alignment', function () {
    $data = ['header' => 'Serial'];
    $config = SerializeConfig::fromArray($data);

    expect($config->getHeader())->toBe('Serial')
        ->and($config->getAlign())->toBe(AlignType::LEFT);
});

test('can create from array with custom alignment', function () {
    $data = [
        'header' => 'Serial',
        'align' => 'center',
    ];
    $config = SerializeConfig::fromArray($data);

    expect($config->getHeader())->toBe('Serial')
        ->and($config->getAlign())->toBe(AlignType::CENTER);
});

test('can convert to array with default alignment', function () {
    $config = new SerializeConfig('Serial');
    $array = $config->toArray();
    expect($array)->toBe([
        'header' => 'Serial',
        'align' => 'left',
    ]);
});

test('can convert to array with custom alignment', function () {
    $config = new SerializeConfig('Serial', AlignType::CENTER);
    $array = $config->toArray();

    expect($array)->toBe([
        'header' => 'Serial',
        'align' => 'center',
    ]);
});
