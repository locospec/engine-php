<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ActionItem;
use LCSEngine\Schemas\Query\ActionOption;

uses()->group('query');

test('can create ActionItem instance with required properties', function () {
    $item = new ActionItem('edit', 'Edit', '/edit', 'pencil');

    expect($item)->toBeInstanceOf(ActionItem::class);
    expect($item->getKey())->toBe('edit');
    expect($item->getLabel())->toBe('Edit');
    expect($item->getUrl())->toBe('/edit');
    expect($item->getIcon())->toBe('pencil');
    expect($item->getConfirmation())->toBeFalse();
    expect($item->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create ActionItem instance with confirmation', function () {
    $item = new ActionItem('delete', 'Delete', '/delete', 'trash', true);

    expect($item)->toBeInstanceOf(ActionItem::class);
    expect($item->getKey())->toBe('delete');
    expect($item->getLabel())->toBe('Delete');
    expect($item->getUrl())->toBe('/delete');
    expect($item->getIcon())->toBe('trash');
    expect($item->getConfirmation())->toBeTrue();
    expect($item->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can add and remove options', function () {
    $item = new ActionItem('edit', 'Edit', '/edit', 'pencil');

    $option1 = new ActionOption('quick', 'Quick Edit', '/quick-edit');
    $option2 = new ActionOption('full', 'Full Edit', '/full-edit');

    $item->addOption($option1);
    expect($item->getOptions())->toHaveCount(1);
    expect($item->getOptions()->first())->toBe($option1);

    $item->addOption($option2);
    expect($item->getOptions())->toHaveCount(2);
    expect($item->getOptions()->last())->toBe($option2);

    $item->removeOption('quick');
    expect($item->getOptions())->toHaveCount(1);
    expect($item->getOptions()->first())->toBe($option2);
});

test('can create ActionItem from array with options', function () {
    $data = [
        'key' => 'edit',
        'label' => 'Edit',
        'url' => '/edit',
        'icon' => 'pencil',
        'confirmation' => true,
        'options' => [
            [
                'key' => 'quick',
                'label' => 'Quick Edit',
                'url' => '/quick-edit',
            ],
            [
                'key' => 'full',
                'label' => 'Full Edit',
                'url' => '/full-edit',
            ],
        ],
    ];

    $item = ActionItem::fromArray($data);

    expect($item)->toBeInstanceOf(ActionItem::class);
    expect($item->getKey())->toBe('edit');
    expect($item->getLabel())->toBe('Edit');
    expect($item->getUrl())->toBe('/edit');
    expect($item->getIcon())->toBe('pencil');
    expect($item->getConfirmation())->toBeTrue();
    expect($item->getOptions())->toHaveCount(2);

    $options = $item->getOptions();
    expect($options->first()->getKey())->toBe('quick');
    expect($options->last()->getKey())->toBe('full');
});

test('can create ActionItem from array without optional properties', function () {
    $data = [
        'key' => 'edit',
        'label' => 'Edit',
    ];

    $item = ActionItem::fromArray($data);

    expect($item)->toBeInstanceOf(ActionItem::class);
    expect($item->getKey())->toBe('edit');
    expect($item->getLabel())->toBe('Edit');
    expect($item->getUrl())->toBe('');
    expect($item->getIcon())->toBe('');
    expect($item->getConfirmation())->toBeFalse();
    expect($item->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create action item instance with required properties', function () {
    $item = new ActionItem('edit', 'Edit');

    expect($item->getKey())->toBe('edit')
        ->and($item->getLabel())->toBe('Edit')
        ->and($item->getUrl())->toBe('')
        ->and($item->getIcon())->toBe('')
        ->and($item->getConfirmation())->toBeFalse();
});

test('can create action item instance with optional properties', function () {
    $item = new ActionItem('edit', 'Edit', '/edit/{id}', 'pencil', true);

    expect($item->getKey())->toBe('edit')
        ->and($item->getLabel())->toBe('Edit')
        ->and($item->getUrl())->toBe('/edit/{id}')
        ->and($item->getIcon())->toBe('pencil')
        ->and($item->getConfirmation())->toBeTrue();
});

test('can create from array', function () {
    $data = [
        'key' => 'edit',
        'label' => 'Edit',
        'url' => '/edit/{id}',
        'icon' => 'pencil',
        'confirmation' => true,
        'options' => [
            [
                'key' => 'delete',
                'label' => 'Delete',
                'url' => '/delete/{id}',
            ],
        ],
    ];

    $item = ActionItem::fromArray($data);

    expect($item->getKey())->toBe('edit')
        ->and($item->getLabel())->toBe('Edit')
        ->and($item->getUrl())->toBe('/edit/{id}')
        ->and($item->getIcon())->toBe('pencil')
        ->and($item->getConfirmation())->toBeTrue()
        ->and($item->getOptions())->toHaveCount(1);
});

test('can convert to array with minimal properties', function () {
    $item = new ActionItem('edit', 'Edit');
    $array = $item->toArray();

    expect($array)->toBe([
        'key' => 'edit',
        'label' => 'Edit',
    ]);
});

test('can convert to array with all properties', function () {
    $item = new ActionItem('edit', 'Edit', '/edit/{id}', 'pencil', true);
    $item->addOption(new ActionOption('delete', 'Delete', '/delete/{id}'));
    $array = $item->toArray();

    expect($array)->toBe([
        'key' => 'edit',
        'label' => 'Edit',
        'url' => '/edit/{id}',
        'icon' => 'pencil',
        'confirmation' => true,
        'options' => [
            [
                'key' => 'delete',
                'label' => 'Delete',
                'url' => '/delete/{id}',
            ],
        ],
    ]);
});
