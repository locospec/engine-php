<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ActionConfig;
use LCSEngine\Schemas\Query\ActionItem;
use LCSEngine\Schemas\Query\ActionOption;

uses()->group('query');

test('can create ActionConfig instance with items and header', function () {
    $items = new Collection();
    $config = new ActionConfig('Actions', $items);

    expect($config)->toBeInstanceOf(ActionConfig::class);
    expect($config->getHeader())->toBe('Actions');
    expect($config->getItems())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create ActionConfig instance without header', function () {
    $items = new Collection();
    $config = new ActionConfig('', $items);

    expect($config)->toBeInstanceOf(ActionConfig::class);
    expect($config->getHeader())->toBe('');
    expect($config->getItems())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create action config instance', function () {
    $items = new Collection();
    $config = new ActionConfig('Actions', $items);

    expect($config->getHeader())->toBe('Actions')
        ->and($config->getItems())->toBe($items);
});

test('can add and remove action items', function () {
    $items = new Collection();
    $config = new ActionConfig('Actions', $items);

    $item1 = new ActionItem('edit', 'Edit', '/edit', 'pencil');
    $item2 = new ActionItem('delete', 'Delete', '/delete', 'trash');

    $config->addItem($item1);
    expect($config->getItems())->toHaveCount(1);
    expect($config->getItems()->first())->toBe($item1);

    $config->addItem($item2);
    expect($config->getItems())->toHaveCount(2);
    expect($config->getItems()->last())->toBe($item2);

    $config->removeItem('edit');
    expect($config->getItems())->toHaveCount(1);
    expect($config->getItems()->first())->toBe($item2);
});

test('can create from array', function () {
    $data = [
        'header' => 'Actions',
        'items' => [
            [
                'key' => 'edit',
                'label' => 'Edit',
                'url' => '/edit',
                'icon' => 'pencil',
                'confirmation' => false,
                'options' => [
                    [
                        'key' => 'quick',
                        'label' => 'Quick Edit',
                        'url' => '/quick-edit',
                    ],
                ],
            ],
        ],
    ];

    $config = ActionConfig::fromArray($data);

    expect($config->getHeader())->toBe('Actions')
        ->and($config->getItems())->toHaveCount(1)
        ->and($config->getItems()->first()->getKey())->toBe('edit')
        ->and($config->getItems()->first()->getLabel())->toBe('Edit')
        ->and($config->getItems()->first()->getUrl())->toBe('/edit')
        ->and($config->getItems()->first()->getIcon())->toBe('pencil')
        ->and($config->getItems()->first()->getConfirmation())->toBeFalse()
        ->and($config->getItems()->first()->getOptions())->toHaveCount(1);
});

test('can convert to array', function () {
    $items = new Collection();
    $config = new ActionConfig('Actions', $items);
    $item = new ActionItem('edit', 'Edit', '/edit/{id}', 'pencil', true);
    $item->addOption(new ActionOption('delete', 'Delete', '/delete/{id}'));
    $config->addItem($item);

    $array = $config->toArray();

    expect($array)->toBe([
        'header' => 'Actions',
        'items' => [
            [
                'key' => 'edit',
                'label' => 'Edit',
                'url' => '/edit/{id}',
                'icon' => 'pencil',
                'confirmation' => true,
                'options' => [
                    [
                        'key' => 'delete',
                        'label' => 'Delete',
                        'url' => '/delete/{id}'
                    ]
                ]
            ]
        ]
    ]);
});
