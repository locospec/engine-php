<?php

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Query\ColumnItem;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\Query;
use LCSEngine\Schemas\Query\SectionItem;

uses()->group('query');

test('can create entity layout with simple fields', function () {
    $query = new Query('test', 'Test Query', 'test_model', new Collection(['field1', 'field2']));

    $query->addEntityLayoutItem(new FieldItem('listing_id'));
    $query->addEntityLayoutItem(new FieldItem('property_id'));
    $query->addEntityLayoutItem(new FieldItem('owner_type'));

    $data = $query->toArray();
    expect($data['entityLayout'])->toBe([
        'listing_id',
        'property_id',
        'owner_type'
    ]);
});

test('can create entity layout with sections and columns', function () {
    $query = new Query('test', 'Test Query', 'test_model', new Collection(['field1', 'field2']));

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
    expect($data['entityLayout'])->toEqual([
        [
            '$Financials',
            ['@Prices', 'reserve_price', 'emd_amount'],
            ['@Deadlines', 'emd_last_date']
        ]
    ]);
});

test('can create entity layout with mixed named and unnamed columns', function () {
    $query = new Query('test', 'Test Query', 'test_model', new Collection(['field1', 'field2']));

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
    expect($data['entityLayout'])->toEqual([
        [
            '$Location & Contact',
            ['@Address', 'address', 'city_name', 'locality.name'],
            ['bank_name', 'bank_branch_name', 'contact']
        ]
    ]);
});

test('can create entity layout with nested sections', function () {
    $query = new Query('test', 'Test Query', 'test_model', new Collection(['field1', 'field2']));

    // Create Meta section
    $metaSection = new SectionItem('Meta');

    // Create Details column
    $detailsColumn = new ColumnItem('Details');

    // Create nested Timestamps section
    $timestampsSection = new SectionItem('Timestamps');

    // Create Times column in Timestamps section
    $timesColumn = new ColumnItem('Times');
    $timesColumn->addItem(new FieldItem('meta.created_at'));
    $timesColumn->addItem(new FieldItem('meta.updated_at'));
    $timestampsSection->addColumn($timesColumn);

    $detailsColumn->addItem($timestampsSection);
    $detailsColumn->addItem(new FieldItem('meta.someFlag'));

    $metaSection->addColumn($detailsColumn);
    $query->addEntityLayoutItem($metaSection);

    $data = $query->toArray();
    expect($data['entityLayout'])->toEqual([
        [
            '$Meta',
            [
                '@Details',
                [
                    '$Timestamps',
                    ['@Times', 'meta.created_at', 'meta.updated_at']
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
        'attributes' => ['field1', 'field2'],
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

    $query = Query::fromArray($data);
    $result = $query->toArray();

    expect($result['entityLayout'])->toEqual($data['entityLayout']);
});
