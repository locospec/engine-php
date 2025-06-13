<?php

namespace LCSEngine\Tests\Schemas\Query\EntityLayout;

use LCSEngine\Schemas\Query\EntityLayout\EntityLayoutBuilder;
use LCSEngine\Schemas\Query\EntityLayout\Field;
use LCSEngine\Schemas\Query\EntityLayout\Section;

uses()->group('query');

test('can add sections and export layout as array', function () {
    $builder = new EntityLayoutBuilder;

    $builder->addSection(
        (new Section('Main'))
            ->addField(new Field('id', 'ID'))
            ->addField(new Field('title', 'Title'))
    );

    $array = $builder->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveCount(1)
        ->and($array[0]['fields'])->toHaveCount(2);
});

test('can add complex sections and export layout as array', function () {
    $builder = new EntityLayoutBuilder;

    $builder = new EntityLayoutBuilder;

    $basicInfo = (new Section('Basic Info'))
        ->addField(new Field('listing_id', 'Listing id'))
        ->addField(new Field('property_id', 'Property id'))
        ->addField(new Field('owner_type', 'Owner type'))
        ->addField(new Field('property_listing_type', 'Property listing type'))
        ->addField(new Field('area', 'Area'))
        ->addField(new Field('borrower_name', 'Borrower name'));

    $financial = (new Section('Financial'))
        ->addField(new Field('reserve_price', 'Reserve price'))
        ->addField(new Field('emd_amount', 'Emd amount'));

    $combined = (new Section('Basic and Financial Info'))
        ->addSection($basicInfo)
        ->addSection($financial);

    $auction = (new Section('Auction Info'))
        ->addSection(
            (new Section(''))->addField(new Field('auction_start_date_time', 'Auction start date time'))
                ->addField(new Field('auction_end_date_time', 'Auction end date time'))
        )
        ->addSection(
            (new Section(''))->addField(new Field('emd_last_date', 'Emd last date'))
        );

    $location = (new Section('Location'))
        ->addField(new Field('address', 'Address'))
        ->addField(new Field('city_name', 'City name'))
        ->addField(new Field('locality.name', 'Locality.name'));

    $institution = (new Section('Institution'))
        ->addField(new Field('bank_name', 'Bank name'))
        ->addField(new Field('bank_branch_name', 'Bank branch name'))
        ->addField(new Field('sub_asset_type_name', 'Sub asset type name'));

    $other = (new Section('Other Details'))
        ->addField(new Field('contact', 'Contact'))
        ->addField(new Field('description', 'Description'))
        ->addField(new Field('view_count', 'View count'))
        ->addField(new Field('interested_count', 'Interested count'));

    $builder
        ->addSection($combined)
        ->addSection($auction)
        ->addSection($location)
        ->addSection($institution)
        ->addSection($other);

    $array = $builder->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveCount(5)
        ->and($array[0]['section'])->toBe('Basic and Financial Info')
        ->and($array[0]['fields'])->toHaveCount(2)
        ->and($array[1]['section'])->toBe('Auction Info')
        ->and($array[1]['fields'])->toHaveCount(2)
        ->and($array[4]['fields'])->toHaveCount(4)
        ->and($array[4]['fields'][0]['key'])->toBe('contact');
});

test('can rebuild layout from array', function () {
    $data = [
        [
            'section' => 'Outer',
            'fields' => [
                ['key' => 'x', 'label' => 'X', 'type' => 'string'],
                [
                    'section' => 'Inner',
                    'fields' => [
                        ['key' => 'y', 'label' => 'Y', 'type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    $builder = EntityLayoutBuilder::fromArray($data);

    expect($builder->toArray())->toBe($data);
});

test('can convert from shorthand to full layout', function () {
    $shorthand = [
        [
            '$ Basic and Financial Info',
            [
                '$ Basic Info',
                'listing_id',
                'property_id',
                'owner_type',
                'property_listing_type',
                'area',
                'borrower_name',
            ],
            [
                '$ Financial',
                'reserve_price',
                'emd_amount',
            ],
        ],
        [
            '$ Auction Info',
            ['auction_start_date_time', 'auction_end_date_time'],
            ['emd_last_date'],
        ],
        ['$ Location', 'address', 'city_name', 'locality.name'],
        ['$ Institution', 'bank_name', 'bank_branch_name', 'sub_asset_type_name'],
        [
            '$ Other Details',
            'contact',
            'description',
            'view_count',
            'interested_count',
        ],
    ];

    $layout = EntityLayoutBuilder::fromShorthand($shorthand);
    $result = $layout->toArray();

    expect($result)->toBeArray()->toHaveCount(5);

    expect($result[0]['section'])->toBe('Basic and Financial Info');
    expect($result[0]['fields'][0]['section'])->toBe('Basic Info');
    expect($result[0]['fields'][0]['fields'])->toHaveCount(6);
    expect($result[0]['fields'][1]['section'])->toBe('Financial');
    expect($result[0]['fields'][1]['fields'])->toHaveCount(2);

    expect($result[1]['section'])->toBe('Auction Info');
    expect($result[1]['fields'][0]['fields'])->toHaveCount(2);
    expect($result[1]['fields'][1]['fields'])->toHaveCount(1);

    expect($result[2]['section'])->toBe('Location');
    expect($result[2]['fields'])->toHaveCount(3);
    expect($result[2]['fields'][0]['key'])->toBe('address');

    expect($result[3]['section'])->toBe('Institution');
    expect($result[3]['fields'])->toHaveCount(3);

    expect($result[4]['section'])->toBe('Other Details');
    expect($result[4]['fields'])->toHaveCount(4);
});
