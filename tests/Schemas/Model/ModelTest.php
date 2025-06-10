<?php

namespace LCSEngine\Tests\Schemas\Model;

use Illuminate\Support\Collection;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Configuration;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Type;
use Mockery;

uses()->group('model');

test('can create Model instance using constructor and has correct initial state', function () {
    $model = new Model('user', 'User Profiles');

    expect($model)->toBeInstanceOf(Model::class);
    expect($model->getName())->toBe('user');
    expect($model->getLabel())->toBe('User Profiles');
    expect($model->getType())->toBe(Type::MODEL);

    expect($model->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getRelationships())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getScopes())->toBeInstanceOf(Collection::class)->toHaveCount(0);

    $config = $model->getConfig();

    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getSingular())->toBe('user');
    expect($config->getPlural())->toBe('users');
});

test('can create Model from array with basic properties and config', function () {
    $modelData = [
        'name' => 'products',
        'label' => 'Products Listing',
        'type' => 'model',
        'config' => [
            'connection' => 'reporting_db',
            'table' => 'product_details',
            'softDelete' => false,
        ],
        'attributes' => [],
        'relationships' => [],
        'scopes' => [],
    ];

    $model = Model::fromArray($modelData);

    expect($model)->toBeInstanceOf(Model::class);
    expect($model->getName())->toBe('products');
    expect($model->getLabel())->toBe('Products Listing');
    expect($model->getType())->toBe(Type::MODEL);

    // Assert Configuration object is created and properties are set
    $config = $model->getConfig();
    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getConnection())->toBe('reporting_db');
    expect($config->getTable())->toBe('product_details');
    expect($config->getSoftDelete())->toBe(false); // Assuming a method like getSoftDelete

    // Assert that other properties are initialized (even if empty from input)
    expect($model->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getRelationships())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getScopes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create Model from array with minimal required properties and missing optional fields', function () {
    $modelData = [
        'name' => 'orders',
        'label' => 'Customer Orders',
        'config' => [
            'connection' => 'sales_db',
        ],
        'attributes' => [],
        'relationships' => [],
        'scopes' => [],
    ];

    $model = Model::fromArray($modelData);

    expect($model)->toBeInstanceOf(Model::class);
    expect($model->getName())->toBe('orders');
    expect($model->getLabel())->toBe('Customer Orders');
    expect($model->getType())->toBe(Type::MODEL);

    $config = $model->getConfig();
    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getConnection())->toBe('sales_db');
    expect($config->getTable())->toBe('orders');

    expect($config->getSingular())->toBe('order');
    expect($config->getPlural())->toBe('orders');
    expect($config->getSoftDelete())->toBe(true);

    expect($model->getAttributes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getRelationships())->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($model->getScopes())->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

test('can create Model from array with populated attributes, relationships, and scopes', function () {
    // Data for nested structures based on provided example
    $modelData = [
        'name' => 'user',
        'label' => 'User Label',
        'type' => 'model',
        'config' => [
            'connection' => 'reporting_db',
            'softDelete' => false,
        ],
        'attributes' => [
            'uuid' => [
                'type' => 'uuid',
                'label' => 'ID',
                'primaryKey' => true,
                'generators' => [
                    [
                        'type' => 'uuid',
                        'operations' => ['insert'],
                    ],
                ],
                'validators' => [
                    [
                        'type' => 'required',
                        'message' => 'UUID is required.',
                        'operations' => ['insert', 'update'],
                    ],
                ],
            ],
            'name' => [
                'type' => 'string',
                'label' => 'Name',
                'labelKey' => true,
            ],
        ],
        'scopes' => [
            'search' => [
                'op' => 'and',
                'conditions' => [
                    [
                        'attribute' => 'name',
                        'op' => 'contains',
                        'value' => 'rajesh',
                    ],
                ],
            ],
        ],
    ];

    $model = Model::fromArray($modelData);
    // dd($model->toArray());

    expect($model)->toBeInstanceOf(Model::class);
    expect($model->getName())->toBe('user');
    expect($model->getLabel())->toBe('User Label');
    expect($model->getType())->toBe(Type::MODEL);

    $config = $model->getConfig();
    expect($config)->toBeInstanceOf(Configuration::class);
    expect($config->getConnection())->toBe('reporting_db');
    expect($config->getSoftDelete())->toBe(false);

    expect($config->getTable())->toBe('users');

    expect($config->getSingular())->toBe('user');
    expect($config->getPlural())->toBe('users');

    $attributes = $model->getAttributes();
    expect($attributes)->toBeInstanceOf(Collection::class)->toHaveCount(2);

    $relationships = $model->getRelationships();
    expect($relationships)->toBeInstanceOf(Collection::class)->toHaveCount(0);

    $scopes = $model->getScopes();
    expect($scopes)->toBeInstanceOf(Collection::class)->toHaveCount(count($modelData['scopes']));
});

test('can add relationships from array', function () {
    $relationshipsData = [
        'belongs_to' => [
            'product' => [
                'type' => 'belongs_to',
                'model' => 'product',
                'foreignKey' => 'product_id',
                'ownerKey' => 'uuid',
            ],
        ],
    ];

    $primaryKey = Mockery::mock();
    $primaryKey->shouldReceive('getName')->andReturn('id');

    $relatedModel = Mockery::mock();
    $relatedModel->shouldReceive('getPrimaryKey')->andReturn($primaryKey);

    $registryManager = Mockery::mock(RegistryManager::class);
    $registryManager->shouldReceive('get')
        ->with('model', 'product')
        ->andReturn($relatedModel);
    $registryManager->shouldReceive('get')
        ->with('model', 'orderItem')
        ->andReturn($relatedModel);

    $model = new Model('orderItem', 'Order Item');
    $model->addRelationshipsFromArray($model->getName(), $relationshipsData, $registryManager);

    $relationship = $model->getRelationship('product');
    expect($model->getRelationships())->toHaveCount(1)
        ->and($relationship)->toBeInstanceOf(\LCSEngine\Schemas\Model\Relationships\BelongsTo::class);
});
