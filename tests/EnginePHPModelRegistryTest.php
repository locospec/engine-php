<?php

use Locospec\EnginePhp\EnginePhpClass;
use Locospec\EnginePhp\Models\ModelRegistry;
use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

beforeEach(function () {
    $this->engine = new EnginePhpClass();
    ModelRegistry::getInstance()->clear();

    // Sample valid model specification
    $this->validModelSpec = [
        'name' => 'properties',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid'
        ],
        'schema' => [
            'uuid' => 'uuid',
            'listing_id' => 'string',
            'property_id' => 'string',
            'reserve_price' => 'string',
            'sub_asset_type_uuid' => 'uuid',
            'city_uuid' => 'uuid',
            'branch_uuid' => 'uuid'
        ],
        'relationships' => [
            'belongs_to' => [
                'sub_asset' => [
                    'model' => 'sub_asset_types',
                    'foreignKey' => 'sub_asset_type_uuid',
                    'ownerKey' => 'uuid'
                ]
            ]
        ]
    ];
});

test('it can process a single model specification', function () {
    $json = json_encode([
        'type' => 'model',
        'name' => 'banks',
        'config' => [
            'primaryKey' => 'uuid'
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
            'slug' => 'string'
        ]
    ]);

    $this->engine->processSpecificationJson($json);

    expect($this->engine->hasModel('banks'))->toBeTrue()
        ->and($this->engine->getModel('banks')->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it can process multiple models from array', function () {
    $json = json_encode([$this->validModelSpec]);
    $this->engine->processSpecificationJson($json);

    $model = $this->engine->getModel('properties');

    expect($this->engine->hasModel('properties'))->toBeTrue()
        ->and($model->getConfig()->getPrimaryKey())->toBe('uuid')
        ->and($model->getSchema()->getProperty('uuid')->getType())->toBe('uuid')
        ->and($model->getSchema()->getProperty('listing_id')->getType())->toBe('string');

    $relationships = $model->getRelationships();
    expect($relationships)->toHaveKey('sub_asset')
        ->and($relationships['sub_asset']->getType())->toBe('belongs_to');
});

test('it can process specification from file', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'test_spec');
    file_put_contents($tempFile, json_encode([$this->validModelSpec]));

    $this->engine->processSpecificationFile($tempFile);

    expect($this->engine->hasModel('properties'))->toBeTrue();

    unlink($tempFile);
});

test('it handles complex relationships correctly', function () {
    $spec = [
        'name' => 'bank_branches',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
            'sortBy' => [['attribute' => 'uuid', 'direction' => 'ASC']]
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string'
        ],
        'relationships' => [
            'belongs_to' => [
                'bank' => ['model' => 'banks'],
                'city' => ['model' => 'cities']
            ],
            'has_many' => [
                'properties' => []
            ]
        ]
    ];

    $this->engine->processSpecificationJson(json_encode($spec));
    $model = $this->engine->getModel('bank_branches');

    $belongsTo = $model->getRelationshipsByType('belongs_to');
    $hasMany = $model->getRelationshipsByType('has_many');

    expect($belongsTo)->toHaveCount(2)
        ->and($hasMany)->toHaveCount(1)
        ->and($belongsTo['bank']->getRelatedModel())->toBe('banks');
});

test('it processes the complete JSON structure correctly', function () {
    $completeSpec = [
        [
            'name' => 'asset_types',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'name' => 'string',
                'slug' => 'string'
            ],
            'relationships' => [
                'has_many' => [
                    'sub_asset_types' => [
                        'model' => 'sub_asset_types'
                    ]
                ]
            ]
        ],
        [
            'name' => 'sub_asset_types',
            'type' => 'model',
            'config' => [
                'primaryKey' => 'uuid',
                'sortBy' => [['attribute' => 'uuid', 'direction' => 'ASC']]
            ],
            'schema' => [
                'uuid' => 'uuid',
                'name' => 'string',
                'slug' => 'string',
                'asset_type_uuid' => 'uuid'
            ]
        ]
    ];

    $this->engine->processSpecificationJson(json_encode($completeSpec));

    expect($this->engine->getAllModels())->toHaveCount(2)
        ->and($this->engine->hasModel('asset_types'))->toBeTrue()
        ->and($this->engine->hasModel('sub_asset_types'))->toBeTrue();

    $assetType = $this->engine->getModel('asset_types');
    $subAssetType = $this->engine->getModel('sub_asset_types');

    expect($assetType->getRelationships())->toHaveKey('sub_asset_types')
        ->and($subAssetType->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it throws exception for invalid JSON', function () {
    $this->engine->processSpecificationJson('{invalid json}');
})->throws(InvalidArgumentException::class);

test('it throws exception for missing type', function () {
    $invalidSpec = [
        'name' => 'test'
    ];
    $this->engine->processSpecificationJson(json_encode([$invalidSpec]));
})->throws(InvalidArgumentException::class);

test('it throws exception for invalid file path', function () {
    $this->engine->processSpecificationFile('/nonexistent/path/to/file.json');
})->throws(InvalidArgumentException::class);

test('it maintains relationship integrity across models', function () {
    $specs = [
        [
            'name' => 'cities',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'district_uuid' => 'uuid'
            ],
            'relationships' => [
                'belongs_to' => [
                    'district' => ['model' => 'districts']
                ],
                'has_many' => [
                    'properties' => []
                ]
            ]
        ],
        [
            'name' => 'districts',
            'type' => 'model',
            'config' => ['primaryKey' => 'uuid'],
            'schema' => [
                'uuid' => 'uuid',
                'state_uuid' => 'uuid'
            ],
            'relationships' => [
                'belongs_to' => [
                    'state' => ['model' => 'states']
                ],
                'has_many' => [
                    'cities' => []
                ]
            ]
        ]
    ];

    $this->engine->processSpecificationJson(json_encode($specs));

    $city = $this->engine->getModel('cities');
    $district = $this->engine->getModel('districts');

    expect($city->getRelationships())->toHaveKey('district')
        ->and($district->getRelationships())->toHaveKey('cities')
        ->and($city->getRelationship('district')->getRelatedModel())->toBe('districts')
        ->and($district->getRelationship('cities')->getRelatedModel())->toBe('cities');
});

test('it throws exception for invalid JSON string', function () {
    $this->engine->processSpecificationJson('{invalid json}');
})->throws(InvalidArgumentException::class, 'Invalid JSON provided');

test('it throws exception for missing type in single object', function () {
    $invalidSpec = [[
        'name' => 'test',
    ]];

    $this->engine->processSpecificationJson(json_encode($invalidSpec));
})->throws(InvalidArgumentException::class, 'Specification must include a type');

test('it throws exception for missing type in array of objects', function () {
    $invalidSpecs = [
        [
            'name' => 'test1',
            'type' => 'model'
        ],
        [
            'name' => 'test2',
            'name' => 'Test Model 2' // missing type
        ]
    ];

    $this->engine->processSpecificationJson(json_encode($invalidSpecs));
})->throws(InvalidArgumentException::class, 'Specification must include a type');

test('it throws exception for unsupported type', function () {
    $invalidSpec = [
        '$id' => 'test',
        'type' => 'unsupported_type'
    ];

    $this->engine->processSpecificationJson(json_encode($invalidSpec));
})->throws(InvalidArgumentException::class, 'Unsupported specification type');
