<?php

namespace LCSEngine\Tests\Schemas\Model;

use LCSEngine\Schemas\Model\Configuration;

uses()->group('model');

test('can create Configuration from array with all properties', function () {
    $modelName = 'item';
    $configData = [
        'connection' => 'test_connection',
        'table' => 'test_table',
        'singular' => 'item',
        'plural' => 'items',
        'softDelete' => true,
    ];

    $config = Configuration::fromArray($modelName, $configData);

    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getConnection())->toBe('test_connection');
    expect($config->getTable())->toBe('test_table');
    expect($config->getSingular())->toBe('item');
    expect($config->getPlural())->toBe('items');
    expect($config->getSoftDelete())->toBe(true);
});

test('can create Configuration from array with minimal required properties', function () {
    $modelName = 'user';
    $configData = [
        'connection' => 'default',
    ];

    $config = Configuration::fromArray($modelName, $configData);

    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getConnection())->toBe('default');
    // Assert default values for optional properties
    expect($config->getTable())->toBe('users');
    expect($config->getSingular())->toBe('user');
    expect($config->getPlural())->toBe('users');
    expect($config->getSoftDelete())->toBe(true);
});

// Add tests for handling unexpected data types or missing required fields if your fromArray validates this.
