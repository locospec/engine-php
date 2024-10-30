<?php

use Locospec\EnginePhp\EnginePhpClass;
use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Models\ModelRegistry;

beforeEach(function () {
    $this->engine = new EnginePhpClass;
    ModelRegistry::getInstance()->clear();

    // Sample valid model specification
    $this->validModelSpec = [
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

    expect($this->engine->hasModel('bank'))->toBeTrue()
        ->and($this->engine->getModel('bank')->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it can process multiple models from array', function () {
    $json = json_encode([$this->validModelSpec]);
    $this->engine->processSpecificationJson($json);

    $model = $this->engine->getModel('property');

    expect($this->engine->hasModel('property'))->toBeTrue()
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

    expect($this->engine->hasModel('property'))->toBeTrue();

    unlink($tempFile);
});

test('it handles complex relationships correctly', function () {
    $spec = [
        'name' => 'bank_branch',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'uuid',
            'sortBy' => [['attribute' => 'uuid', 'direction' => 'ASC']],
        ],
        'schema' => [
            'uuid' => 'uuid',
            'name' => 'string',
        ],
        'relationships' => [
            'belongs_to' => [
                'bank' => ['model' => 'bank'],
                'city' => ['model' => 'city'],
            ],
            'has_many' => [
                'properties' => [],
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode($spec));
    $model = $this->engine->getModel('bank_branch');

    $belongsTo = $model->getRelationshipsByType('belongs_to');
    $hasMany = $model->getRelationshipsByType('has_many');

    expect($belongsTo)->toHaveCount(2)
        ->and($hasMany)->toHaveCount(1)
        ->and($belongsTo['bank']->getRelatedModel())->toBe('bank');
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

    expect($this->engine->getAllModels())->toHaveCount(2)
        ->and($this->engine->hasModel('asset_type'))->toBeTrue()
        ->and($this->engine->hasModel('sub_asset_type'))->toBeTrue();

    $assetType = $this->engine->getModel('asset_type');
    $subAssetType = $this->engine->getModel('sub_asset_type');

    expect($assetType->getRelationships())->toHaveKey('sub_asset_types')
        ->and($subAssetType->getConfig()->getPrimaryKey())->toBe('uuid');
});

test('it throws exception for invalid JSON', function () {
    $this->engine->processSpecificationJson('{invalid json}');
})->throws(InvalidArgumentException::class);

test('it throws exception for missing type', function () {
    $invalidSpec = [
        'name' => 'test',
    ];
    $this->engine->processSpecificationJson(json_encode([$invalidSpec]));
})->throws(InvalidArgumentException::class);

test('it throws exception for invalid file path', function () {
    $this->engine->processSpecificationFile('/nonexistent/path/to/file.json');
})->throws(InvalidArgumentException::class);

test('it maintains relationship integrity across models', function () {
    $specs = [
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
                    'properties' => [],
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
                    'state' => ['model' => 'state'],
                ],
                'has_many' => [
                    'cities' => [],
                ],
            ],
        ],
    ];

    $this->engine->processSpecificationJson(json_encode($specs));

    $city = $this->engine->getModel('city');
    $district = $this->engine->getModel('district');

    expect($city->getRelationships())->toHaveKey('district')
        ->and($district->getRelationships())->toHaveKey('cities')
        ->and($city->getRelationship('district')->getRelatedModel())->toBe('district')
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
            'name' => 'test',
            'type' => 'model',
        ],
        [
            'name' => 'anothertest',
        ],
    ];

    $this->engine->processSpecificationJson(json_encode($invalidSpecs));
})->throws(InvalidArgumentException::class, 'Specification must include a type');

test('it throws exception for unsupported type', function () {
    $invalidSpec = [
        '$id' => 'test',
        'type' => 'unsupported_type',
    ];

    $this->engine->processSpecificationJson(json_encode($invalidSpec));
})->throws(InvalidArgumentException::class, 'Unsupported specification type');
