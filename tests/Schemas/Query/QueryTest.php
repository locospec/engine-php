<?php

namespace LCSEngine\Tests\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Option;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Query\ActionConfig\ActionConfig;
use LCSEngine\Schemas\Query\ActionConfig\ActionItem;
use LCSEngine\Schemas\Query\AlignType;
use LCSEngine\Schemas\Query\EntityLayout\EntityLayoutBuilder;
use LCSEngine\Schemas\Query\EntityLayout\Field;
use LCSEngine\Schemas\Query\EntityLayout\Section;
use LCSEngine\Schemas\Query\LensSimpleFilter\LensFilterType;
use LCSEngine\Schemas\Query\LensSimpleFilter\LensSimpleFilter;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Query\SelectionType;
use LCSEngine\Schemas\Query\SerializeConfig;
use LCSEngine\Schemas\Type;
use Mockery;

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
        'uuid',
        // Add any other attributes that might be used across these tests
    ]);

    // Mock the status attribute with options
    $this->statusAttribute = Mockery::mock(Attribute::class);
    $this->statusAttribute->shouldReceive('getName')->andReturn('status');
    $this->statusAttribute->shouldReceive('getType')->andReturn(AttributeType::STRING);
    $this->statusAttribute->shouldReceive('getOptions')->andReturn(new Collection([
        Option::fromArray(['id' => 'active', 'const' => 'ACTIVE', 'title' => 'Active']),
        Option::fromArray(['id' => 'inactive', 'const' => 'INACTIVE', 'title' => 'Inactive']),
    ]));
    $this->statusAttribute->shouldReceive('toArray')->andReturn(['name' => 'status']);

    // Mock the category attribute with options
    $this->categoryAttribute = Mockery::mock(Attribute::class);
    $this->categoryAttribute->shouldReceive('getName')->andReturn('category');
    $this->categoryAttribute->shouldReceive('getType')->andReturn(AttributeType::STRING);
    $this->categoryAttribute->shouldReceive('getOptions')->andReturn(new Collection([
        Option::fromArray(['id' => 'premium', 'const' => 'PREMIUM', 'title' => 'Premium']),
        Option::fromArray(['id' => 'basic', 'const' => 'BASIC', 'title' => 'Basic']),
    ]));
    $this->categoryAttribute->shouldReceive('toArray')->andReturn(['name' => 'category']);

    $mockAttributesCollection = new Collection;
    foreach ($this->mockAllPossibleUserAttributes as $attributeName) {
        if ($attributeName === 'status') {
            $mockAttributesCollection->put($attributeName, $this->statusAttribute);
        } elseif ($attributeName === 'category') {
            $mockAttributesCollection->put($attributeName, $this->categoryAttribute);
        } else {
            $mockAttribute = Mockery::mock(Attribute::class);
            $mockAttribute->shouldReceive('getName')->andReturn($attributeName);
            $mockAttribute->shouldReceive('getType')->andReturn(AttributeType::STRING);
            $mockAttribute->shouldReceive('toArray')->andReturn(['name' => $attributeName]);
            $mockAttributesCollection->put($attributeName, $mockAttribute);
        }
    }

    $this->mockModel = Mockery::mock(Model::class);
    $this->mockModel->shouldReceive('getAttributes')->andReturn($mockAttributesCollection);
    $this->mockModel->shouldReceive('getName')->andReturn('user');
    $this->mockModel->shouldReceive('getLabel')->andReturn('User');
    $this->mockModel->shouldReceive('getAttribute')->with('status')->andReturn($this->statusAttribute);
    $this->mockModel->shouldReceive('getAttribute')->with('category')->andReturn($this->categoryAttribute);

    // Mock the Collection that getScopes() returns
    $mockScopesCollection = new Collection;
    $mockScopesCollection->put('search', Mockery::mock('LCSEngine\Schemas\Model\Filters\Filters')); // Ensure 'search' scope is available for validation
    $mockScopesCollection->put('active', Mockery::mock('LCSEngine\Schemas\Model\Filters\Filters')); // Ensure 'search' scope is available for validation
    $mockScopesCollection->put('verified', Mockery::mock('LCSEngine\Schemas\Model\Filters\Filters')); // Ensure 'search' scope is available for validation

    $this->mockModel->shouldReceive('getScopes')
        ->andReturn($mockScopesCollection);

    $this->mockRegistryManager = Mockery::mock(RegistryManager::class);
    $this->mockRegistryManager->shouldReceive('get')
        ->with('model', 'user')
        ->andReturn($this->mockModel);
});

test('can create Query instance using constructor and has correct initial state', function () {
    $attributes = ['id', 'name', 'email'];
    $query = new Query('users', 'User List', $attributes, $this->mockModel);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModelName())->toBe('user');
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

    $query = Query::fromArray($queryData, $this->mockRegistryManager);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModelName())->toBe('user');
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
        'lensSimpleFilters' => [
            'name' => [
                'type' => 'enum',
                'model' => 'user',
                'label' => 'Name Filter',
                'options' => [
                    ['id' => 'John', 'const' => 'JOHN', 'title' => 'John'],
                    ['id' => 'Jane', 'const' => 'JANE', 'title' => 'Jane'],
                ],
                'dependsOn' => ['category'],
            ],
            'email' => [
                'type' => 'date',
                'model' => 'user',
                'label' => 'Email Date Filter',
                'options' => [],
                'dependsOn' => [],
            ],
        ],
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
            [
                '$Personal Info',
                ['$Basic Info', 'name', 'email'],
            ],
            [
                '$Address',
                ['$Location', 'street', 'city', 'country'],
            ],
        ],
    ];

    $query = Query::fromArray($queryData, $this->mockRegistryManager);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getName())->toBe('users');
    expect($query->getLabel())->toBe('User List');
    expect($query->getModelName())->toBe('user');
    expect($query->getType())->toBe(Type::QUERY);
    expect($query->getSelectionType())->toBe(SelectionType::MULTIPLE);
    expect($query->getSelectionKey())->toBe('id');

    expect($query->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(8);
    expect($query->getLensFilters())->toBeInstanceOf(Collection::class)->toHaveCount(2);

    expect($query->getExpand())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getAllowedScopes())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getEntityLayout())->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($query->getSerialize()->getHeader())->toBe('#');
    expect($query->getSerialize()->getAlign())->toBe(AlignType::RIGHT);
    expect($query->getActions()->getHeader())->toBe('Actions');
    expect($query->getActions()->getItems())->toBeInstanceOf(Collection::class)->toHaveCount(1);
});

test('can create Query from array with shorthand lensSimpleFilters', function () {
    $queryData = [
        'name' => 'users',
        'label' => 'User List',
        'type' => 'query',
        'model' => 'user',
        'attributes' => ['status', 'category'],
        'lensSimpleFilters' => ['status', 'category'],
    ];

    $query = Query::fromArray($queryData, $this->mockRegistryManager);

    expect($query)->toBeInstanceOf(Query::class);
    expect($query->getLensFilters())->toBeInstanceOf(Collection::class)->toHaveCount(2);

    // Verify status filter
    $statusFilter = $query->getLensFilters()->get('status');
    expect($statusFilter)->toBeInstanceOf(LensSimpleFilter::class);
    expect($statusFilter->getName())->toBe('status');
    expect($statusFilter->getType())->toBe(LensFilterType::ENUM);
    expect($statusFilter->getModelName())->toBe('user');
    expect($statusFilter->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(2);

    // Verify category filter
    $categoryFilter = $query->getLensFilters()->get('category');
    expect($categoryFilter)->toBeInstanceOf(LensSimpleFilter::class);
    expect($categoryFilter->getName())->toBe('category');
    expect($categoryFilter->getType())->toBe(LensFilterType::ENUM);
    expect($categoryFilter->getModelName())->toBe('user');
    expect($categoryFilter->getOptions())->toBeInstanceOf(Collection::class)->toHaveCount(2);
});

test('can add and remove attributes', function () {
    $initialAttributes = ['id', 'name'];
    $query = new Query('users', 'User List', $initialAttributes, $this->mockModel);

    $query->addAttribute('email', $this->mockModel);
    expect($query->getAttributes())->toHaveCount(3);
    expect($query->getAttributes()->keys()->toArray())->toEqual(['id', 'name', 'email']);

    $query->removeAttribute('name');
    expect($query->getAttributes())->toHaveCount(2);
    expect($query->getAttributes()->keys()->toArray())->toEqual(['id', 'email']);
});

test('can add and remove lens filters', function () {
    $initialAttributes = ['id', 'name', 'status', 'category'];
    $query = new Query('users', 'User List', $initialAttributes, $this->mockModel);

    $filter1 = new LensSimpleFilter('name', LensFilterType::ENUM->value, 'user');
    $filter1->setLabel('Name Filter');
    $filter1->addOption(Option::fromArray(['id' => 'John', 'const' => 'JOHN', 'title' => 'John']));

    $filter2 = new LensSimpleFilter('email', LensFilterType::DATE->value, 'user');
    $filter2->setLabel('Email Filter');

    $query->addLensFilter($filter1);
    $query->addLensFilter($filter2);

    expect($query->getLensFilters())->toHaveCount(2);
    expect($query->getLensFilters()->keys()->toArray())->toEqual(['name', 'email']);

    $query->removeLensFilter('name');
    expect($query->getLensFilters())->toHaveCount(1);
    expect($query->getLensFilters()->keys()->toArray())->toEqual(['email']);
});

test('can add and remove expand fields', function () {
    $initialAttributes = ['id', 'name', 'profile', 'roles', 'author', 'comments'];
    $query = new Query('users', 'User List', $initialAttributes, $this->mockModel);

    $query->addExpand('profile');
    $query->addExpand('roles');
    expect($query->getExpand())->toHaveCount(2);
    expect($query->getExpand()->toArray())->toEqual(['profile', 'roles']);

    $query->removeExpand('profile');
    expect($query->getExpand())->toHaveCount(1);
    expect($query->getExpand()->toArray())->toEqual(['roles']);
});

test('can add and remove allowed scopes', function () {
    $initialAttributes = ['id', 'name', 'active', 'verified'];
    $query = new Query('users', 'User List', $initialAttributes, $this->mockModel);

    $query->addAllowedScope('active', $this->mockModel);
    $query->addAllowedScope('verified', $this->mockModel);
    expect($query->getAllowedScopes())->toHaveCount(2);
    expect($query->getAllowedScopes()->toArray())->toEqual(['active', 'verified']);
    $query->removeAllowedScope('active');
    expect($query->getAllowedScopes())->toHaveCount(1);
    expect($query->getAllowedScopes()->toArray())->toEqual(['verified']);
});

test('can set and get selection type and key', function () {
    $attributes = ['id', 'name', 'uuid'];
    $query = new Query('users', 'User List', $attributes, $this->mockModel);

    $query->setSelectionType(SelectionType::MULTIPLE);
    $query->setSelectionKey('uuid');

    expect($query->getSelectionType())->toBe(SelectionType::MULTIPLE);
    expect($query->getSelectionKey())->toBe('uuid');
});

test('can add entity layout items', function () {
    $initialAttributes = ['id', 'name', 'email', 'street', 'city', 'country'];
    $query = new Query('users', 'User List', $initialAttributes, $this->mockModel);
    $builder = new EntityLayoutBuilder;

    $builder->addSection(
        (new Section('Personal Info'))
            ->addField(new Field('id', 'ID'))
            ->addField(new Field('name', 'Name'))
    );

    $query->setEntityLayout($builder->getSections());

    expect($query->getEntityLayout())->toHaveCount(1);

    $builder->addSection(
        (new Section('Location Info'))
            ->addField(new Field('city', 'City'))
            ->addField(new Field('country', 'Country'))
    );

    $query->setEntityLayout($builder->getSections());

    expect($query->getEntityLayout())->toHaveCount(2);

    $firstSection = $query->getEntityLayout()->first();
    expect($firstSection->getLabel())->toBe('Personal Info')
        ->and($firstSection->getFields())->toHaveCount(2);
});

test('query toArray method returns correct array structure', function () {
    $attributes = ['id', 'name', 'email'];
    $query = new Query('users', 'User List', $attributes, $this->mockModel);

    $statusFilter = new LensSimpleFilter('status', LensFilterType::ENUM->value, 'user');
    $statusFilter->setLabel('Status Filter');
    $statusFilter->addOption(Option::fromArray(['const' => 'active', 'title' => 'ACTIVE']));
    $query->addLensFilter($statusFilter);

    $query->addExpand('profile');
    $query->addAllowedScope('search', $this->mockModel);
    $query->setSelectionType(SelectionType::SINGLE);
    $query->setSelectionKey('id');

    $actionConfig = new ActionConfig('', new Collection);
    $actionItem = new ActionItem('view', 'View Item', '/items/{id}');
    $actionConfig->addItem($actionItem);
    $query->setActions($actionConfig);

    $serializeConfig = new SerializeConfig('');
    $serializeConfig->setHeader('Order');
    $serializeConfig->setAlign(AlignType::CENTER);
    $query->setSerialize($serializeConfig);

    $builder = new EntityLayoutBuilder;

    $builder->addSection(
        (new Section('Contact'))
            ->addField(new Field('email', 'Email'))
            ->addField(new Field('name', 'Name'))
    );

    $query->setEntityLayout($builder->getSections());

    $expectedArray = [
        'name' => 'users',
        'label' => 'User List',
        'type' => 'query',
        'model' => 'user',
        'attributes' => [
            'id' => ['name' => 'id'],
            'name' => ['name' => 'name'],
            'email' => ['name' => 'email'],
        ],
        'lensSimpleFilters' => [
            'status' => [
                'name' => 'status',
                'type' => 'enum',
                'model' => 'user',
                'label' => 'Status Filter',
                'options' => [
                    ['id' => null, 'const' => 'active', 'title' => 'ACTIVE'],
                ],
            ],
        ],
        'expand' => ['profile'],
        'allowedScopes' => ['search'],
        'selectionType' => 'single',
        'selectionKey' => 'id',
        'actions' => [
            'header' => '',
            'items' => [
                [
                    'key' => 'view',
                    'label' => 'View Item',
                    'url' => '/items/{id}',
                ],
            ],
        ],
        'serialize' => [
            'header' => 'Order',
            'align' => 'center',
        ],
        'entityLayout' => [
            [
                'section' => 'Contact',
                'fields' => [
                    [
                        'key' => 'email',
                        'label' => 'Email',
                        'type' => 'string',
                    ],
                    [
                        'key' => 'name',
                        'label' => 'Name',
                        'type' => 'string',
                    ],
                ],
            ],
        ],
    ];

    expect($query->toArray())->toEqual($expectedArray);
});
