<?php

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ColumnItem;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Query\SectionItem;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use Mockery;

uses()->group('query');

beforeEach(function () {
    $this->mockAllPossibleAttributes = new Collection([
        'listing_id',
        'property_id',
        'owner_type',
        'reserve_price',
        'emd_amount',
        'emd_last_date',
        'address',
        'city_name',
        'locality',
        'bank_name',
        'bank_branch_name',
        'contact',
        'created_at',
        'updated_at',
        'meta'
    ]);

    $mockAttributesCollection = new Collection();
    foreach ($this->mockAllPossibleAttributes as $attributeName) {
        $mockAttribute = Mockery::mock(Attribute::class);
        $mockAttribute->shouldReceive('getName')->andReturn($attributeName);
        $mockAttribute->shouldReceive('toArray')->andReturn(['name' => $attributeName]);
        $mockAttributesCollection->put($attributeName, $mockAttribute);
    }

    $this->mockModel = Mockery::mock(Model::class);
    $this->mockModel->shouldReceive('getAttributes')->andReturn($mockAttributesCollection);

    $this->mockRegistryManager = Mockery::mock(RegistryManager::class);
    $this->mockRegistryManager->shouldReceive('get')
        ->with('model', 'test_model')
        ->andReturn($this->mockModel);
});

test('can create entity layout with simple fields', function () {
    $initialAttributes = ['listing_id', 'property_id', 'owner_type'];
    $query = new Query('test', 'Test Query', 'test_model', $initialAttributes, $this->mockRegistryManager);

    $query->addEntityLayoutItem(new FieldItem('listing_id'));
    $query->addEntityLayoutItem(new FieldItem('property_id'));
    $query->addEntityLayoutItem(new FieldItem('owner_type'));

    $data = $query->toArray();

    expect($data['attributes'])->toEqual([
        'listing_id' => [
            'name' => 'listing_id'
        ],
        'property_id' => [
            'name' => 'property_id'
        ],
        'owner_type' => [
            'name' => 'owner_type'
        ]
    ]);

    expect($data['entityLayout'])->toBe([
        'listing_id',
        'property_id',
        'owner_type'
    ]);
});

test('can create entity layout with sections and columns', function () {
    $initialAttributes = ['reserve_price', 'emd_amount', 'emd_last_date'];
    $query = new Query('test', 'Test Query', 'test_model', $initialAttributes, $this->mockRegistryManager);

    // Create Financials section
    $financialsSection = new SectionItem('Financials');

    // Create Prices column
    $pricesColumn = new ColumnItem('Prices');
    $pricesColumn->addItem(new FieldItem('reserve_price'));
    $pricesColumn->addItem(new FieldItem('emd_amount'));
    $financialsSection->addColumn($pricesColumn);

    // Create Deadlines column
    $deadlinesColumn = new ColumnItem('Deadlines');
    $deadlinesColumn->addItem(new FieldItem('emd_last_date'));
    $financialsSection->addColumn($deadlinesColumn);

    $query->addEntityLayoutItem($financialsSection);

    $data = $query->toArray();
    expect($data['attributes'])->toEqual(['reserve_price' => ['name' => 'reserve_price'], 'emd_amount' => ['name' => 'emd_amount'], 'emd_last_date' => ['name' => 'emd_last_date']]);
    expect($data['entityLayout'])->toEqual([
        [
            '$Financials',
            ['@Prices', 'reserve_price', 'emd_amount'],
            ['@Deadlines', 'emd_last_date']
        ]
    ]);
});

test('can create entity layout with mixed named and unnamed columns', function () {
    $initialAttributes = ['address', 'city_name', 'locality', 'bank_name', 'bank_branch_name', 'contact'];
    $query = new Query('test', 'Test Query', 'test_model', $initialAttributes, $this->mockRegistryManager);

    // Create Location & Contact section
    $locationSection = new SectionItem('Location & Contact');

    // Create Address column (named)
    $addressColumn = new ColumnItem('Address');
    $addressColumn->addItem(new FieldItem('address'));
    $addressColumn->addItem(new FieldItem('city_name'));
    $addressColumn->addItem(new FieldItem('locality.name'));
    $locationSection->addColumn($addressColumn);

    // Create unnamed column
    $unnamedColumn = new ColumnItem();
    $unnamedColumn->addItem(new FieldItem('bank_name'));
    $unnamedColumn->addItem(new FieldItem('bank_branch_name'));
    $unnamedColumn->addItem(new FieldItem('contact'));
    $locationSection->addColumn($unnamedColumn);

    $query->addEntityLayoutItem($locationSection);

    $data = $query->toArray();

    expect($data['attributes'])->toEqual([
        'address' => ['name' => 'address'],
        'city_name' => ['name' => 'city_name'],
        'locality' => ['name' => 'locality'],
        'bank_name' => ['name' => 'bank_name'],
        'bank_branch_name' => ['name' => 'bank_branch_name'],
        'contact' => ['name' => 'contact']
    ]);
    expect($data['entityLayout'])->toEqual([
        [
            '$Location & Contact',
            ['@Address', 'address', 'city_name', 'locality.name'],
            ['bank_name', 'bank_branch_name', 'contact']
        ]
    ]);
});

test('can create entity layout with nested sections', function () {
    $initialAttributes = ['created_at', 'updated_at', 'meta'];
    $query = new Query('test', 'Test Query', 'test_model', $initialAttributes, $this->mockRegistryManager);

    // Create Meta section
    $metaSection = new SectionItem('Meta');

    // Create Details column
    $detailsColumn = new ColumnItem('Details');

    // Create nested Timestamps section
    $timestampsSection = new SectionItem('Timestamps');

    // Create Times column in Timestamps section
    $timesColumn = new ColumnItem('Times');
    $timesColumn->addItem(new FieldItem('created_at'));
    $timesColumn->addItem(new FieldItem('updated_at'));
    $timestampsSection->addColumn($timesColumn);

    $detailsColumn->addItem($timestampsSection);
    $detailsColumn->addItem(new FieldItem('meta.someFlag'));

    $metaSection->addColumn($detailsColumn);
    $query->addEntityLayoutItem($metaSection);

    $data = $query->toArray();
    expect($data['attributes'])->toEqual([
        'created_at' => ['name' => 'created_at'],
        'updated_at' => ['name' => 'updated_at'],
        'meta' => ['name' => 'meta']
    ]);
    expect($data['entityLayout'])->toEqual([
        [
            '$Meta',
            [
                '@Details',
                [
                    '$Timestamps',
                    ['@Times', 'created_at', 'updated_at']
                ],
                'meta.someFlag'
            ]
        ]
    ]);
});

test('can create entity layout from array', function () {
    $data = [
        'name' => 'test',
        'label' => 'Test Query',
        'type' => 'query',
        'model' => 'test_model',
        'attributes' => [
            'listing_id',
            'property_id',
            'owner_type',
            'reserve_price',
            'emd_amount',
            'emd_last_date',
            'address',
            'city_name',
            'locality',
            'bank_name',
            'bank_branch_name',
            'contact'
        ],
        'entityLayout' => [
            'listing_id',
            'property_id',
            [
                '$Financials',
                ['@Prices', 'reserve_price', 'emd_amount'],
                ['@Deadlines', 'emd_last_date']
            ],
            [
                '$Location & Contact',
                ['@Address', 'address', 'city_name', 'locality.name'],
                ['bank_name', 'bank_branch_name', 'contact']
            ]
        ]
    ];

    // This part now needs to mock attributes based on $data['attributes'] specifically for this test
    $mockAttributesForFromArray = new Collection();
    foreach ($data['attributes'] as $attributeName) {
        $mockAttribute = Mockery::mock(Attribute::class);
        $mockAttribute->shouldReceive('getName')->andReturn($attributeName);
        $mockAttribute->shouldReceive('toArray')->andReturn(['name' => $attributeName]);
        $mockAttributesForFromArray->put($attributeName, $mockAttribute);
    }

    $mockModelForFromArray = Mockery::mock(Model::class);
    $mockModelForFromArray->shouldReceive('getAttributes')->andReturn($mockAttributesForFromArray);

    $mockRegistryManagerForFromArray = Mockery::mock(RegistryManager::class);
    $mockRegistryManagerForFromArray->shouldReceive('get')
        ->with('model', 'test_model')
        ->andReturn($mockModelForFromArray);

    $query = Query::fromArray($data, $mockRegistryManagerForFromArray);
    $result = $query->toArray();

    expect($result['attributes'])->toEqual([
        'listing_id' => ['name' => 'listing_id'],
        'property_id' => ['name' => 'property_id'],
        'owner_type' => ['name' => 'owner_type'],
        'reserve_price' => ['name' => 'reserve_price'],
        'emd_amount' => ['name' => 'emd_amount'],
        'emd_last_date' => ['name' => 'emd_last_date'],
        'address' => ['name' => 'address'],
        'city_name' => ['name' => 'city_name'],
        'locality' => ['name' => 'locality'],
        'bank_name' => ['name' => 'bank_name'],
        'bank_branch_name' => ['name' => 'bank_branch_name'],
        'contact' => ['name' => 'contact']
    ]);
    expect($result['entityLayout'])->toEqual($data['entityLayout']);
});
