<?php

namespace LCSEngine\Tests\Schemas\Mutator;

use LCSEngine\Schemas\Mutator\Mutator;
use LCSEngine\Schemas\Mutator\DbOpType;
use LCSEngine\Schemas\Mutator\UISchema;
use LCSEngine\Schemas\Mutator\LayoutType;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;
use LCSEngine\Schemas\Model\Model;
use Illuminate\Support\Collection;
use Mockery;

uses()->group('mutator');

beforeEach(function () {
    $this->model = Mockery::mock(Model::class);
    $this->model->shouldReceive('getName')->andReturn('test-model');
    $this->model->shouldReceive('getAttributes')->andReturn(collect([
        'title' => new Attribute('title', 'Title', AttributeType::STRING),
        'description' => new Attribute('description', 'Description', AttributeType::TEXT)
    ]));
});

test('can create mutator with required parameters', function () {
    $mutator = new Mutator(
        'test-mutator',
        'Test Mutator',
        DbOpType::CREATE,
        'test-model'
    );

    expect($mutator)
        ->toBeInstanceOf(Mutator::class)
        ->and($mutator->getName())->toBe('test-mutator')
        ->and($mutator->getLabel())->toBe('Test Mutator')
        ->and($mutator->getDbOp())->toBe(DbOpType::CREATE)
        ->and($mutator->getModelName())->toBe('test-model')
        ->and($mutator->getType()->value)->toBe('mutator')
        ->and($mutator->getAttributes())->toBeInstanceOf(Collection::class)
        ->and($mutator->getAttributes())->toBeEmpty()
        ->and($mutator->getUISchema())->toBeNull();
});

test('can add and remove attributes', function () {
    $mutator = new Mutator(
        'test-mutator',
        'Test Mutator',
        DbOpType::CREATE,
        'test-model'
    );

    $attribute = new Attribute('title', 'Title', AttributeType::STRING);
    $mutator->addAttribute($attribute);

    expect($mutator->getAttributes())
        ->toHaveCount(1)
        ->and($mutator->hasAttribute('title'))->toBeTrue()
        ->and($mutator->getAttribute('title'))->toBe($attribute);

    $mutator->removeAttribute('title');

    expect($mutator->getAttributes())
        ->toBeEmpty()
        ->and($mutator->hasAttribute('title'))->toBeFalse()
        ->and($mutator->getAttribute('title'))->toBeNull();
});

test('can set and get ui schema', function () {
    $mutator = new Mutator(
        'test-mutator',
        'Test Mutator',
        DbOpType::CREATE,
        'test-model'
    );

    $uiSchema = new UISchema(
        LayoutType::VERTICAL_LAYOUT,
        new Collection()
    );

    $mutator->setUISchema($uiSchema);

    expect($mutator->getUISchema())->toBe($uiSchema);
});

test('can create mutator from array', function () {
    $data = [
        'name' => 'test-mutator',
        'label' => 'Test Mutator',
        'type' => 'mutator',
        'dbOp' => 'create',
        'model' => 'test-model',
        'attributes' => [
            'title' => [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'string',
                'relatedModelName' => 'User',
                'dependsOn' => ['user_id', 'role_id']
            ],
            'description' => [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'text'
            ]
        ],
        'uiSchema' => [
            'type' => 'VerticalLayout',
            'elements' => []
        ]
    ];

    $mutator = Mutator::fromArray($data, $this->model);

    expect($mutator)
        ->toBeInstanceOf(Mutator::class)
        ->and($mutator->getName())->toBe('test-mutator')
        ->and($mutator->getLabel())->toBe('Test Mutator')
        ->and($mutator->getDbOp())->toBe(DbOpType::CREATE)
        ->and($mutator->getModelName())->toBe('test-model')
        ->and($mutator->getAttributes())->toHaveCount(2)
        ->and($mutator->hasAttribute('title'))->toBeTrue()
        ->and($mutator->hasAttribute('description'))->toBeTrue()
        ->and($mutator->getUISchema())->toBeInstanceOf(UISchema::class);

    // Test new attribute properties
    $titleAttribute = $mutator->getAttribute('title');
    expect($titleAttribute->getRelatedModelName())->toBe('User')
        ->and($titleAttribute->getDependsOn())->toHaveCount(2)
        ->and($titleAttribute->getDependsOn()->toArray())->toContain('user_id', 'role_id');
});

test('can convert mutator to array', function () {
    $mutator = new Mutator(
        'test-mutator',
        'Test Mutator',
        DbOpType::CREATE,
        'test-model'
    );

    $attribute = new Attribute('title', 'Title', AttributeType::STRING);
    $attribute->setRelatedModelName('User');
    $attribute->setDependsOn('user_id');
    $attribute->setDependsOn('role_id');
    $mutator->addAttribute($attribute);

    $uiSchema = new UISchema(
        LayoutType::VERTICAL_LAYOUT,
        new Collection()
    );
    $mutator->setUISchema($uiSchema);

    $array = $mutator->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['name', 'label', 'type', 'dbOp', 'model', 'attributes', 'uiSchema'])
        ->and($array['name'])->toBe('test-mutator')
        ->and($array['label'])->toBe('Test Mutator')
        ->and($array['type'])->toBe('mutator')
        ->and($array['dbOp'])->toBe('create')
        ->and($array['model'])->toBe('test-model')
        ->and($array['attributes'])->toBeArray()
        ->and($array['attributes'])->toHaveCount(1)
        ->and($array['attributes']['title']['relatedModelName'])->toBe('User')
        ->and($array['attributes']['title']['dependsOn'])->toBeArray()
        ->and($array['attributes']['title']['dependsOn'])->toHaveCount(2)
        ->and($array['attributes']['title']['dependsOn'])->toContain('user_id', 'role_id')
        ->and($array['uiSchema'])->toBeArray();
});

test('can create mutator from array without optional fields', function () {
    $data = [
        'name' => 'test-mutator',
        'label' => 'Test Mutator',
        'type' => 'mutator',
        'dbOp' => 'create',
        'model' => 'test-model'
    ];

    $mutator = Mutator::fromArray($data, $this->model);

    expect($mutator)
        ->toBeInstanceOf(Mutator::class)
        ->and($mutator->getName())->toBe('test-mutator')
        ->and($mutator->getLabel())->toBe('Test Mutator')
        ->and($mutator->getDbOp())->toBe(DbOpType::CREATE)
        ->and($mutator->getModelName())->toBe('test-model')
        ->and($mutator->getAttributes())->toBeEmpty()
        ->and($mutator->getUISchema())->toBeNull();
});

test('can create mutator with complex ui schema', function () {
    $uiSchema = [
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

    $mutator = new Mutator(
        'update-property',
        'Update Property',
        DbOpType::UPDATE,
        'property'
    );

    $mutator->setUISchema(UISchema::fromArray($uiSchema));

    expect($mutator)
        ->toBeInstanceOf(Mutator::class)
        ->and($mutator->getName())->toBe('update-property')
        ->and($mutator->getLabel())->toBe('Update Property')
        ->and($mutator->getDbOp())->toBe(DbOpType::UPDATE)
        ->and($mutator->getModelName())->toBe('property')
        ->and($mutator->getUISchema())->toBeInstanceOf(UISchema::class)
        ->and($mutator->getUISchema()->getElements())->toHaveCount(13);
});
