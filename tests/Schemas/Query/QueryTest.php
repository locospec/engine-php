<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ActionConfig;
use LCSEngine\Schemas\Query\ActionItem;
use LCSEngine\Schemas\Query\ActionOption;
use LCSEngine\Schemas\Query\AlignType;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Query\SectionItem;
use LCSEngine\Schemas\Query\SelectionType;
use LCSEngine\Schemas\Query\SerializeConfig;
use LCSEngine\Schemas\Type;

uses()->group('query');

test('can create Query instance using constructor and has correct initial state', function () {
    $attributes = new Collection(['id', 'name', 'email']);
    $query = new Query('users', 'User List', 'user', $attributes);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModel())->toBe('user');
    expect($query->getType())->toBe(Type::QUERY);
    expect($query->getSelectionType())->toBe(SelectionType::NONE);

    expect($query->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(3);
    expect($query->getLensFilters())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getExpand())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getAllowedScopes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getEntityLayout())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getActions())->toBeNull();
    expect($query->getSerialize())->toBeNull();
});

test('can create Query from array with basic properties', function () {
    $queryData = [
        'name' => 'users',
        'label' => 'User List',
        'type' => 'query',
        'model' => 'user',
        'attributes' => ['id', 'name', 'email'],
    ];

    $query = Query::fromArray($queryData);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModel())->toBe('user');
    expect($query->getType())->toBe(Type::QUERY);
    expect($query->getSelectionType())->toBe(SelectionType::NONE);

    expect($query->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(3);
    expect($query->getLensFilters())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getExpand())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getAllowedScopes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getEntityLayout())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($query->getActions())->toBeNull();
    expect($query->getSerialize())->toBeNull();
});

test('can create Query from array with all properties', function () {
    $queryData = [
        'name' => 'users',
        'label' => 'User List',
        'type' => 'query',
        'model' => 'user',
        'attributes' => ['id', 'name', 'email'],
        'lensSimpleFilters' => ['name', 'email'],
        'expand' => ['profile', 'roles'],
        'allowedScopes' => ['active', 'verified'],
        'selectionType' => 'multiple',
        'selectionKey' => 'id',
        'actions' => [
            'header' => 'Actions',
            'items' => [
                [
                    'key' => 'edit',
                    'label' => 'Edit',
                    'url' => '/users/{id}/edit',
                    'icon' => 'pencil',
                    'confirmation' => false,
                    'options' => [
                        [
                            'key' => 'quick',
                            'label' => 'Quick Edit',
                            'url' => '/users/{id}/quick-edit',
                        ],
                    ],
                ],
            ],
        ],
        'serialize' => [
            'header' => '#',
            'align' => 'right',
        ],
        'entityLayout' => [
            // 'id',
            // ['$Personal Info', 'name', 'email'],
            // ['$Address', ['street', 'city', 'country']],
            [
                '$Personal Info',
                "listing_id",
                "property_id",
                "owner_type",
                "property_listing_type",
                "area",
                "borrower_name",
                "reserve_price",
                "emd_amount",
                "auction_start_date_time",
                "auction_end_date_time",
                "emd_last_date"
            ],
            [
                '$Address',
                "address",
                "city_name",
                "locality.name",
                "bank_name",
                "bank_branch_name",
                "sub_asset_type_name",
                "contact",
                "description",
                "view_count",
                "interested_count"
            ]
        ],
    ];

    $query = Query::fromArray($queryData);
    dd($query, $query->toArray());
    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModel())->toBe('user');
    expect($query->getType())->toBe(Type::QUERY);
    expect($query->getSelectionType())->toBe(SelectionType::MULTIPLE);
    expect($query->getSelectionKey())->toBe('id');

    // Test attributes
    expect($query->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(3);
    expect($query->getAttributes()->toArray())->toBe(['id', 'name', 'email']);

    // Test lens filters
    expect($query->getLensFilters())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getLensFilters()->toArray())->toBe(['name', 'email']);

    // Test expand
    expect($query->getExpand())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getExpand()->toArray())->toBe(['profile', 'roles']);

    // Test allowed scopes
    expect($query->getAllowedScopes())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getAllowedScopes()->toArray())->toBe(['active', 'verified']);

    // Test actions
    $actions = $query->getActions();
    expect($actions)->toBeInstanceOf(ActionConfig::class);
    expect($actions->getHeader())->toBe('Actions');
    expect($actions->getItems())->toHaveCount(1);

    $actionItem = $actions->getItems()->first();
    expect($actionItem)->toBeInstanceOf(ActionItem::class);
    expect($actionItem->getKey())->toBe('edit');
    expect($actionItem->getLabel())->toBe('Edit');
    expect($actionItem->getUrl())->toBe('/users/{id}/edit');
    expect($actionItem->getIcon())->toBe('pencil');
    expect($actionItem->getConfirmation())->toBeFalse();
    expect($actionItem->getOptions())->toHaveCount(1);

    $option = $actionItem->getOptions()->first();
    expect($option)->toBeInstanceOf(ActionOption::class);
    expect($option->getKey())->toBe('quick');
    expect($option->getLabel())->toBe('Quick Edit');
    expect($option->getUrl())->toBe('/users/{id}/quick-edit');

    // Test serialize
    $serialize = $query->getSerialize();
    expect($serialize)->toBeInstanceOf(SerializeConfig::class);
    expect($serialize->getHeader())->toBe('#');
    expect($serialize->getAlign())->toBe(AlignType::RIGHT);

    // Test entity layout
    $entityLayout = $query->getEntityLayout();
    expect($entityLayout)->toBeInstanceOf(Collection::class)->toHaveCount(3);

    // First item should be a FieldItem
    $firstItem = $entityLayout->first();
    expect($firstItem)->toBeInstanceOf(FieldItem::class);
    expect($firstItem->getField())->toBe('id');

    // Second item should be a SectionItem
    $secondItem = $entityLayout->get(1);
    expect($secondItem)->toBeInstanceOf(SectionItem::class);
    expect($secondItem->getHeader())->toBe('Personal Info');
    expect($secondItem->getItems())->toHaveCount(2);

    // Third item should be a SectionItem with nested items
    $thirdItem = $entityLayout->get(2);
    expect($thirdItem)->toBeInstanceOf(SectionItem::class);
    expect($thirdItem->getHeader())->toBe('Address');
    expect($thirdItem->getItems())->toHaveCount(3);
});

test('can add and remove attributes', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $query->addAttribute('email');
    expect($query->getAttributes())->toHaveCount(3);
    expect($query->getAttributes()->toArray())->toBe(['id', 'name', 'email']);

    $query->removeAttribute('name');
    expect($query->getAttributes())->toHaveCount(2);
    expect($query->getAttributes()->toArray())->toBe(['id', 'email']);
});

test('can add and remove lens filters', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $query->addLensFilter('name');
    $query->addLensFilter('email');
    expect($query->getLensFilters())->toHaveCount(2);
    expect($query->getLensFilters()->toArray())->toBe(['name', 'email']);

    $query->removeLensFilter('name');
    expect($query->getLensFilters())->toHaveCount(1);
    expect($query->getLensFilters()->toArray())->toBe(['email']);
});

test('can add and remove expand fields', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $query->addExpand('profile');
    $query->addExpand('roles');
    expect($query->getExpand())->toHaveCount(2);
    expect($query->getExpand()->toArray())->toBe(['profile', 'roles']);

    $query->removeExpand('profile');
    expect($query->getExpand())->toHaveCount(1);
    expect($query->getExpand()->toArray())->toBe(['roles']);
});

test('can add and remove allowed scopes', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $query->addAllowedScope('active');
    $query->addAllowedScope('verified');
    expect($query->getAllowedScopes())->toHaveCount(2);
    expect($query->getAllowedScopes()->toArray())->toBe(['active', 'verified']);

    $query->removeAllowedScope('active');
    expect($query->getAllowedScopes())->toHaveCount(1);
    expect($query->getAllowedScopes()->toArray())->toBe(['verified']);
});

test('can set and get selection type and key', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $query->setSelectionType(SelectionType::MULTIPLE);
    expect($query->getSelectionType())->toBe(SelectionType::MULTIPLE);

    $query->setSelectionKey('id');
    expect($query->getSelectionKey())->toBe('id');
});

test('can add entity layout items', function () {
    $attributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $attributes);

    $fieldItem = new FieldItem('id');
    $query->addEntityLayoutItem($fieldItem);

    $sectionItem = new SectionItem('Personal Info');
    $sectionItem->addItem(new FieldItem('name'));
    $sectionItem->addItem(new FieldItem('email'));
    $query->addEntityLayoutItem($sectionItem);

    expect($query->getEntityLayout())->toHaveCount(2);
    expect($query->getEntityLayout()->first())->toBeInstanceOf(FieldItem::class);
    expect($query->getEntityLayout()->last())->toBeInstanceOf(SectionItem::class);
    expect($query->getEntityLayout()->last()->getItems())->toHaveCount(2);
});
