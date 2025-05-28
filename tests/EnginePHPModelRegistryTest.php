<?php

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;

beforeAll(function () {
    LCS::bootstrap();
});

beforeEach(function () {
    $this->engine = new LCS;
    $this->engine->getRegistryManager()->getRegistry('model')->clear();
});

test('it can process a single model specification', function () {
    $json = json_encode([
        'type' => 'model',
        'name' => 'bank',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
            'slug' => 'string',
        ],
    ]);

    $this->engine->processSpecificationJson($json);
    $registryManager = $this->engine->getRegistryManager();

    expect($registryManager->has('model', 'bank'))->toBeTrue()
        ->and($registryManager->get('model', 'bank')->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it can process multiple models from array', function () {
    // Define both models with their relationships
    $subAssetSpec = [
        'name' => 'sub_asset_type',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
        ],
    ];

    $propertySpec = [
        'name' => 'property',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'listing_id' => 'string',
            'property_id' => 'string',
            'reserve_price' => 'string',
            'sub_asset_type_uuid' => 'uuid',
            'city_uuid' => 'uuid',
            'branch_uuid' => 'uuid',
        ],
        'relationships' => [
            'belongs_to' => [
                'sub_asset' => [
                    'model' => 'sub_asset_type',
                    'foreignKey' => 'sub_asset_type_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode([$subAssetSpec, $propertySpec]));

    $registryManager = $this->engine->getRegistryManager();
    $model = $registryManager->get('model', 'property');

    expect($registryManager->has('model', 'property'))->toBeTrue()
        ->and($model->getConfig()->getPrimaryKey())->toBe('uuid')
        ->and($model->getSchema()->getProperty('uuid')->getType())->toBe('uuid')
        ->and($model->getSchema()->getProperty('listing_id')->getType())->toBe('string');

    $relationships = $model->getRelationships();
    expect($relationships)->toHaveKey('sub_asset')
        ->and($relationships['sub_asset']->getType())->toBe('belongs_to');
});

test('it can process specification from file', function () {
    $subAssetSpec = [
        'name' => 'sub_asset_type',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
        ],
    ];

    $propertySpec = [
        'name' => 'property',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'listing_id' => 'string',
            'property_id' => 'string',
            'reserve_price' => 'string',
            'sub_asset_type_uuid' => 'uuid',
            'city_uuid' => 'uuid',
            'branch_uuid' => 'uuid',
        ],
        'relationships' => [
            'belongs_to' => [
                'sub_asset' => [
                    'model' => 'sub_asset_type',
                    'foreignKey' => 'sub_asset_type_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
        ],
    ];

    $tempFile = tempnam(sys_get_temp_dir(), 'test_spec');
    file_put_contents($tempFile, json_encode([$subAssetSpec, $propertySpec]));

    $this->engine->processSpecificationFile($tempFile);

    expect($this->engine->getRegistryManager()->has('model', 'property'))->toBeTrue();

    unlink($tempFile);
});

test('it handles complex relationships correctly', function () {
    $propertySpec = [
        'name' => 'property',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
            'bank_branch_uuid' => 'uuid',
        ],
    ];

    $branchSpec = [
        'name' => 'bank_branch',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
            'sortBy' => [['attribute' => 'uuid', 'direction' => 'ASC']],
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
            'bank_uuid' => 'uuid',
            'city_uuid' => 'uuid',
        ],
        'relationships' => [
            'belongs_to' => [
                'bank' => [
                    'model' => 'bank',
                    'foreignKey' => 'bank_uuid',
                    'ownerKey' => 'uuid',
                ],
                'city' => [
                    'model' => 'city',
                    'foreignKey' => 'city_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
            'has_many' => [
                'properties' => [
                    'model' => 'property',
                    'foreignKey' => 'bank_branch_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
        ],
    ];

    $bankSpec = [
        'name' => 'bank',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
        ],
        'relationships' => [
            'has_many' => [
                'branches' => [
                    'model' => 'bank_branch',
                    'foreignKey' => 'bank_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
        ],
    ];

    $citySpec = [
        'name' => 'city',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
        ],
        'relationships' => [
            'has_many' => [
                'bank_branches' => [
                    'model' => 'bank_branch',
                    'foreignKey' => 'city_uuid',
                    'ownerKey' => 'uuid',
                ],
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode([
        $propertySpec,
        $bankSpec,
        $citySpec,
        $branchSpec,
    ]));

    $registryManager = $this->engine->getRegistryManager();
    $model = $registryManager->get('model', 'bank_branch');

    $belongsTo = $model->getRelationshipsByType('belongs_to');
    $hasMany = $model->getRelationshipsByType('has_many');

    expect($belongsTo)->toHaveCount(2)
        ->and($hasMany)->toHaveCount(1)
        ->and($belongsTo['bank']->getRelatedModelName())->toBe('bank')
        ->and($belongsTo['city']->getRelatedModelName())->toBe('city')
        ->and($belongsTo['bank']->getForeignKey())->toBe('bank_uuid')
        ->and($belongsTo['bank']->getOwnerKey())->toBe('uuid')
        ->and($hasMany['properties']->getRelatedModelName())->toBe('property');
});

test('it processes the complete JSON structure correctly', function () {
    $completeSpec = [
        [
            'name' => 'asset_type',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'name' => 'string',
                'slug' => 'string',
            ],
            'relationships' => [
                'has_many' => [
                    'sub_asset_types' => [
                        'model' => 'sub_asset_type',
                        'foreignKey' => 'asset_type_uuid',
                        'ownerKey' => 'uuid',
                    ],
                ],
            ],
        ],
        [
            'name' => 'sub_asset_type',
            'type' => 'model',
            'config' => [
                'primaryKey' => 'uuid',
                'sortBy' => [['attribute' => 'uuid', 'direction' => 'ASC']],
            ],
            'schema' => [
                'uuid' => 'uuid',
                'name' => 'string',
                'slug' => 'string',
                'asset_type_uuid' => 'uuid',
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode($completeSpec));
    $registryManager = $this->engine->getRegistryManager();

    expect($registryManager->all('model'))->toHaveCount(2)
        ->and($registryManager->has('model', 'asset_type'))->toBeTrue()
        ->and($registryManager->has('model', 'sub_asset_type'))->toBeTrue();

    $assetType = $registryManager->get('model', 'asset_type');
    $subAssetType = $registryManager->get('model', 'sub_asset_type');

    expect($assetType->getRelationships())->toHaveKey('sub_asset_types')
        ->and($subAssetType->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it maintains relationship integrity across models', function () {
    $specs = [
        [
            'name' => 'property',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'city_uuid' => 'uuid',
            ],
        ],
        [
            'name' => 'state',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'name' => 'string',
            ],
        ],
        [
            'name' => 'city',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'district_uuid' => 'uuid',
            ],
            'relationships' => [
                'belongs_to' => [
                    'district' => ['model' => 'district'],
                ],
                'has_many' => [
                    'properties' => [
                        'model' => 'property',
                        'foreignKey' => 'city_uuid',
                        'ownerKey' => 'uuid',
                    ],
                ],
            ],
        ],
        [
            'name' => 'district',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'state_uuid' => 'uuid',
            ],
            'relationships' => [
                'belongs_to' => [
                    'state' => [
                        'model' => 'state',
                        'foreignKey' => 'state_uuid',
                        'ownerKey' => 'uuid',
                    ],
                ],
                'has_many' => [
                    'cities' => [
                        'model' => 'city',
                        'foreignKey' => 'district_uuid',
                        'ownerKey' => 'uuid',
                    ],
                ],
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode($specs));
    $registryManager = $this->engine->getRegistryManager();

    $city = $registryManager->get('model', 'city');
    $district = $registryManager->get('model', 'district');

    expect($city->getRelationships())->toHaveKey('district')
        ->and($district->getRelationships())->toHaveKey('cities')
        ->and($city->getRelationship('district')->getRelatedModelName())->toBe('district')
        ->and($district->getRelationship('cities')->getRelatedModelName())->toBe('city');
});

test('it throws exception for invalid JSON', function () {
    $this->engine->processSpecificationJson('{invalid json}');
})->throws(InvalidArgumentException::class, 'Invalid JSON provided');

test('it throws exception for missing type', function () {
    $invalidSpec = [
        'name' => 'test',
    ];
    $this->engine->processSpecificationJson(json_encode([$invalidSpec]));
})->throws(InvalidArgumentException::class, 'Specification must include a type');

test('it throws exception for invalid file path', function () {
    $this->engine->processSpecificationFile('/nonexistent/path/to/file.json');
})->throws(InvalidArgumentException::class, 'Specification file not found');

test('it throws exception for unsupported type', function () {
    $invalidSpec = [
        'name' => 'test',
        'type' => 'unsupported_type',
    ];

    $this->engine->processSpecificationJson(json_encode($invalidSpec));
})->throws(InvalidArgumentException::class, 'Only model specifications are supported');
