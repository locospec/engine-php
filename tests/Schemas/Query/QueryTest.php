<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ActionConfig;
use LCSEngine\Schemas\Query\ActionItem;
use LCSEngine\Schemas\Query\ActionOption;
use LCSEngine\Schemas\Query\AlignType;
use LCSEngine\Schemas\Query\ColumnItem;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Query\SectionItem;
use LCSEngine\Schemas\Query\SelectionType;
use LCSEngine\Schemas\Query\SerializeConfig;
use LCSEngine\Schemas\Type;
use Mockery;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Attributes\Attribute;

uses()->group('query');

beforeEach(function () {
    $this->mockAllPossibleUserAttributes = new Collection([
        'id',
        'name',
        'email',
        'profile',
        'roles',
        'street',
        'city',
        'country',
        'status',
        'category',
        'author',
        'comments',
        'active',
        'verified',
        // Add any other attributes that might be used across these tests
    ]);

    $mockAttributesCollection = new Collection();
    foreach ($this->mockAllPossibleUserAttributes as $attributeName) {
        $mockAttribute = Mockery::mock(Attribute::class);
        $mockAttribute->shouldReceive('getName')->andReturn($attributeName);
        $mockAttributesCollection->put($attributeName, $mockAttribute);
    }

    $this->mockModel = Mockery::mock(Model::class);
    $this->mockModel->shouldReceive('getAttributes')->andReturn($mockAttributesCollection);

    $this->mockRegistryManager = Mockery::mock(RegistryManager::class);
    $this->mockRegistryManager->shouldReceive('get')
        ->with('model', 'user')
        ->andReturn($this->mockModel);
});

test('can create Query instance using constructor and has correct initial state', function () {
    $attributes = new Collection(['id', 'name', 'email']);
    $query = new Query('users', 'User List', 'user', $attributes, $this->mockRegistryManager);

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

    $attributes = new Collection($queryData['attributes']);
    $query = Query::fromArray($queryData, $this->mockRegistryManager);

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
        'attributes' => ['id', 'name', 'email', 'profile', 'roles', 'street', 'city', 'country'],
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
            'id',
            [
                '$Personal Info',
                ['@Basic Info', 'name', 'email']
            ],
            [
                '$Address',
                ['@Location', 'street', 'city', 'country']
            ]
        ],
    ];

    $attributes = new Collection($queryData['attributes']);
    $query = Query::fromArray($queryData, $this->mockRegistryManager);
    $result = $query->toArray();
    // dd($result);

    expect($result)->toEqual($queryData);
});

test('can add and remove attributes', function () {
    $initialAttributes = new Collection(['id', 'name']);
    $query = new Query('users', 'User List', 'user', $initialAttributes, $this->mockRegistryManager);

    $query->addAttribute('email');
    expect($query->getAttributes())->toHaveCount(3);
    expect($query->getAttributes()->toArray())->toEqual(['id', 'name', 'email']);

    $query->removeAttribute('name');
    expect($query->getAttributes())->toHaveCount(2);
    expect($query->getAttributes()->toArray())->toEqual(['id', 'email']);
});

test('can add and remove lens filters', function () {
    $initialAttributes = new Collection(['id', 'name', 'status', 'category']);
    $query = new Query('users', 'User List', 'user', $initialAttributes, $this->mockRegistryManager);

    $query->addLensFilter('name');
    $query->addLensFilter('email'); // This will still pass even if email is not in mockAllPossibleUserAttributes, as addLensFilter does not validate against model attributes.
    expect($query->getLensFilters())->toHaveCount(2);
    expect($query->getLensFilters()->toArray())->toEqual(['name', 'email']);

    $query->removeLensFilter('name');
    expect($query->getLensFilters())->toHaveCount(1);
    expect($query->getLensFilters()->toArray())->toEqual(['email']);
});

test('can add and remove expand fields', function () {
    $initialAttributes = new Collection(['id', 'name', 'profile', 'roles', 'author', 'comments']);
    $query = new Query('users', 'User List', 'user', $initialAttributes, $this->mockRegistryManager);

    $query->addExpand('profile');
    $query->addExpand('roles');
    expect($query->getExpand())->toHaveCount(2);
    expect($query->getExpand()->toArray())->toEqual(['profile', 'roles']);

    $query->removeExpand('profile');
    expect($query->getExpand())->toHaveCount(1);
    expect($query->getExpand()->toArray())->toEqual(['roles']);
});

test('can add and remove allowed scopes', function () {
    $initialAttributes = new Collection(['id', 'name', 'active', 'verified']);
    $query = new Query('users', 'User List', 'user', $initialAttributes, $this->mockRegistryManager);

    $query->addAllowedScope('active');
    $query->addAllowedScope('verified');
    expect($query->getAllowedScopes())->toHaveCount(2);
    expect($query->getAllowedScopes()->toArray())->toEqual(['active', 'verified']);

    $query->removeAllowedScope('active');
    expect($query->getAllowedScopes())->toHaveCount(1);
    expect($query->getAllowedScopes()->toArray())->toEqual(['verified']);
});

test('can set and get selection type and key', function () {
    $attributes = new Collection(['id', 'name', 'uuid']);
    $query = new Query('users', 'User List', 'user', $attributes, $this->mockRegistryManager);

    $query->setSelectionType(SelectionType::MULTIPLE);
    $query->setSelectionKey('uuid');

    expect($query->getSelectionType())->toBe(SelectionType::MULTIPLE);
    expect($query->getSelectionKey())->toBe('uuid');
});

test('can add entity layout items', function () {
    $initialAttributes = new Collection(['id', 'name', 'email', 'street', 'city', 'country']);
    $query = new Query('users', 'User List', 'user', $initialAttributes, $this->mockRegistryManager);

    $query->addEntityLayoutItem(new FieldItem('id'));
    expect($query->getEntityLayout())->toHaveCount(1);

    $section = new SectionItem('Personal Info');
    $column = new ColumnItem('Basic Info');
    $column->addItem(new FieldItem('name'));
    $column->addItem(new FieldItem('email'));
    $section->addColumn($column);
    $query->addEntityLayoutItem($section);
    expect($query->getEntityLayout())->toHaveCount(2);

    $query->removeEntityLayoutItem(new FieldItem('id'));
    expect($query->getEntityLayout())->toHaveCount(1);
    expect($query->getEntityLayout()->first())->toBeInstanceOf(SectionItem::class);
});

test('can create query from array', function () {
    $data = [
        'name' => 'test_query',
        'label' => 'Test Query',
        'type' => 'query',
        'model' => 'user',
        'attributes' => ['id', 'name', 'email', 'street', 'city', 'country'],
        'lensSimpleFilters' => ['status', 'category'],
        'expand' => ['author', 'comments'],
        'allowedScopes' => ['published', 'featured'],
        'selectionType' => 'multiple',
        'selectionKey' => 'uuid',
        'actions' => [
            'header' => 'Actions',
            'items' => [
                [
                    'key' => 'view',
                    'label' => 'View',
                    'url' => '/view/{id}',
                    'icon' => 'eye',
                ],
            ],
        ],
        'serialize' => [
            'header' => 'Count',
            'align' => 'left',
        ],
        'entityLayout' => [
            'name',
            [
                '$Details',
                ['@User', 'email'],
                ['@Address', 'street', 'city']
            ]
        ],
    ];

    $attributes = new Collection($data['attributes']);
    $query = Query::fromArray($data, $this->mockRegistryManager);
    $result = $query->toArray();
    // dd($result);

    expect($result)->toEqual($data);
});

test('query toArray method returns correct array structure', function () {
    $attributes = new Collection(['id', 'name', 'email']);
    $query = new Query('users', 'User List', 'user', $attributes, $this->mockRegistryManager);
    $query->addLensFilter('status');
    $query->addExpand('profile');
    $query->addAllowedScope('admin');
    $query->setSelectionType(SelectionType::SINGLE);
    $query->setSelectionKey('id');

    $actionConfig = new ActionConfig('', new Collection());
    $actionItem = new ActionItem('view', 'View Item', '/items/{id}');
    $actionConfig->addItem($actionItem);
    $query->setActions($actionConfig);

    $serializeConfig = new SerializeConfig('');
    $serializeConfig->setHeader('Order');
    $serializeConfig->setAlign(AlignType::CENTER);
    $query->setSerialize($serializeConfig);

    $query->addEntityLayoutItem(new FieldItem('name'));
    $section = new SectionItem('Contact');
    $column = new ColumnItem('Details');
    $column->addItem(new FieldItem('email'));
    $section->addColumn($column);
    $query->addEntityLayoutItem($section);

    $expectedArray = [
        'name' => 'users',
        'label' => 'User List',
        'type' => 'query',
        'model' => 'user',
        'attributes' => ['id', 'name', 'email'],
        'lensSimpleFilters' => ['status'],
        'expand' => ['profile'],
        'allowedScopes' => ['admin'],
        'selectionType' => 'single',
        'selectionKey' => 'id',
        'actions' => [
            'header' => null,
            'items' => [
                [
                    'key' => 'view',
                    'label' => 'View Item',
                    'url' => '/items/{id}',
                    'icon' => null,
                    'options' => [],
                    'confirmation' => false,
                ],
            ],
        ],
        'serialize' => [
            'header' => 'Order',
            'align' => 'center',
        ],
        'entityLayout' => [
            'name',
            [
                '$Contact',
                ['@Details', 'email']
            ],
        ],
    ];

    expect($query->toArray())->toEqual($expectedArray);
});
