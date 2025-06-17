<?php

namespace LCSEngine\Tests\Schemas\Mutator;

use LCSEngine\Schemas\Mutator\UISchema;
use LCSEngine\Schemas\Mutator\LayoutType;
use LCSEngine\Schemas\Mutator\UIElement;
use LCSEngine\Schemas\Mutator\UIElementType;
use Illuminate\Support\Collection;

uses()->group('mutator');

test('can create ui schema with required parameters', function () {
    $uiSchema = new UISchema(
        LayoutType::VERTICAL_LAYOUT,
        new Collection()
    );

    expect($uiSchema)
        ->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getType())->toBe(LayoutType::VERTICAL_LAYOUT)
        ->and($uiSchema->getElements())->toBeInstanceOf(Collection::class)
        ->and($uiSchema->getElements())->toBeEmpty()
        ->and($uiSchema->getOptions())->toBeInstanceOf(Collection::class)
        ->and($uiSchema->getOptions())->toBeEmpty();
});

test('can create ui schema with options', function () {
    $options = collect(['option1' => 'value1']);
    $uiSchema = new UISchema(
        LayoutType::VERTICAL_LAYOUT,
        new Collection(),
        $options
    );

    expect($uiSchema)
        ->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getType())->toBe(LayoutType::VERTICAL_LAYOUT)
        ->and($uiSchema->getElements())->toBeEmpty()
        ->and($uiSchema->getOptions())->toBe($options);
});

test('can create ui schema from array', function () {
    $data = [
        'type' => 'VerticalLayout',
        'elements' => [
            [
                'type' => 'Control',
                'scope' => '#/properties/title',
                'label' => 'Title'
            ],
            [
                'type' => 'Control',
                'scope' => '#/properties/description',
                'label' => 'Description'
            ]
        ],
        'options' => [
            'option1' => 'value1'
        ]
    ];

    $uiSchema = UISchema::fromArray($data);

    expect($uiSchema)
        ->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getType())->toBe(LayoutType::VERTICAL_LAYOUT)
        ->and($uiSchema->getElements())->toHaveCount(2)
        ->and($uiSchema->getElements()->first())->toBeInstanceOf(UIElement::class)
        ->and($uiSchema->getElements()->first()->getType())->toBe(UIElementType::CONTROL)
        ->and($uiSchema->getOptions())->toBeInstanceOf(Collection::class)
        ->and($uiSchema->getOptions()->get('option1'))->toBe('value1');
});

test('can create ui schema from complex array', function () {
    $data = [
        'type' => 'VerticalLayout',
        'options' => [
            'rowSpacing' => 3
        ],
        'elements' => [
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/property_id',
                        'label' => 'Property ID'
                    ],
                    [
                        'type' => 'lens-enum',
                        'scope' => '#/properties/sub_asset_type_uuid',
                        'label' => 'Sub Asset Type ID'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-enum',
                        'scope' => '#/properties/bank_uuid',
                        'label' => 'Bank ID'
                    ],
                    [
                        'type' => 'lens-enum',
                        'scope' => '#/properties/city_uuid',
                        'label' => 'City ID'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-enum',
                        'scope' => '#/properties/branch_uuid',
                        'label' => 'Branch ID'
                    ],
                    [
                        'type' => 'lens-enum',
                        'scope' => '#/properties/locality_uuid',
                        'label' => 'Locality ID'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/reserve_price',
                        'label' => 'Reserve Price'
                    ],
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/emd_amount',
                        'label' => 'EMD Amount'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/ab_verified',
                        'label' => 'AB Verified'
                    ],
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/auction_by_ab',
                        'label' => 'Auction By AB'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/featured',
                        'label' => 'Featured Property'
                    ],
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/financing_facility',
                        'label' => 'Financing Facility'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/consulting',
                        'label' => 'Consulting'
                    ],
                    [
                        'type' => 'lens-switch',
                        'scope' => '#/properties/relationship_management',
                        'label' => 'Relationship Management'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/address',
                        'label' => 'Address'
                    ],
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/contact',
                        'label' => 'Contact'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-calendar',
                        'scope' => '#/properties/emd_last_date',
                        'label' => 'EMD Last Date'
                    ],
                    [
                        'type' => 'lens-calendar-date-time',
                        'scope' => '#/properties/auction_start_date_time',
                        'label' => 'Auction Start Date Time'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-calendar-date-time',
                        'scope' => '#/properties/auction_end_date_time',
                        'label' => 'Auction End Date Time'
                    ],
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/description',
                        'label' => 'Description'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/physical_inspection_timeline',
                        'label' => 'Physical Inspection Timeline'
                    ],
                    [
                        'type' => 'lens-dropdown',
                        'scope' => '#/properties/property_date_type',
                        'label' => 'Property Date Type'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-dropdown',
                        'scope' => '#/properties/possession_type',
                        'label' => 'Possession Type'
                    ],
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/area',
                        'label' => 'Area'
                    ]
                ]
            ],
            [
                'type' => 'HorizontalLayout',
                'elements' => [
                    [
                        'type' => 'lens-text-input',
                        'scope' => '#/properties/borrower_name',
                        'label' => 'Borrower Name'
                    ]
                ]
            ]
        ]
    ];

    $uiSchema = UISchema::fromArray($data);

    expect($uiSchema)
        ->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getType())->toBe(LayoutType::VERTICAL_LAYOUT)
        ->and($uiSchema->getElements())->toHaveCount(13)
        ->and($uiSchema->getElements()->first())->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getElements()->first()->getType())->toBe(LayoutType::HORIZONTAL_LAYOUT)
        ->and($uiSchema->getOptions())->toBeInstanceOf(Collection::class)
        ->and($uiSchema->getOptions()->get('rowSpacing'))->toBe(3);
});

test('can convert ui schema to array', function () {
    $elements = collect([
        new UIElement(UIElementType::CONTROL, '#/properties/title', 'Title')
    ]);
    $options = collect(['option1' => 'value1']);

    $uiSchema = new UISchema(
        LayoutType::VERTICAL_LAYOUT,
        $elements,
        $options
    );

    $array = $uiSchema->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['type', 'elements', 'options'])
        ->and($array['type'])->toBe('VerticalLayout')
        ->and($array['elements'])->toBeArray()
        ->and($array['elements'])->toHaveCount(1)
        ->and($array['elements'][0]['type'])->toBe('Control')
        ->and($array['options'])->toBe(['option1' => 'value1']);
});

test('can create ui schema from array without optional fields', function () {
    $data = [
        'type' => 'VerticalLayout',
        'elements' => []
    ];

    $uiSchema = UISchema::fromArray($data);

    expect($uiSchema)
        ->toBeInstanceOf(UISchema::class)
        ->and($uiSchema->getType())->toBe(LayoutType::VERTICAL_LAYOUT)
        ->and($uiSchema->getElements())->toBeEmpty()
        ->and($uiSchema->getOptions())->toBeInstanceOf(Collection::class)
        ->and($uiSchema->getOptions())->toBeEmpty();
});
