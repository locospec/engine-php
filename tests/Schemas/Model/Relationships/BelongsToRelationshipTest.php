<?php

use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\Type;
use Mockery;

uses()->group('relationships');

test('can set and get properties', function () {
    $relationship = new BelongsTo;

    $relationship->setType(Type::BELONGS_TO);
    $relationship->setForeignKey('user_id');
    $relationship->setRelatedModelName('User');
    $relationship->setCurrentModelName('Post');
    $relationship->setRelationshipName('user');
    $relationship->setOwnerKey('id');

    expect($relationship->getType())->toBe(Type::BELONGS_TO);
    expect($relationship->getForeignKey())->toBe('user_id');
    expect($relationship->getRelatedModelName())->toBe('User');
    expect($relationship->getCurrentModelName())->toBe('Post');
    expect($relationship->getRelationshipName())->toBe('user');
    expect($relationship->getOwnerKey())->toBe('id');
});

test('toArray returns correct array structure', function () {
    $relationship = new BelongsTo;

    $relationship->setType(Type::BELONGS_TO);
    $relationship->setForeignKey('user_id');
    $relationship->setRelatedModelName('User');
    $relationship->setCurrentModelName('Post');
    $relationship->setRelationshipName('user');
    $relationship->setOwnerKey('id');

    $expectedArray = [
        'type' => 'belongs_to',
        'foreignKey' => 'user_id',
        'relatedModelName' => 'User',
        'currentModelName' => 'Post',
        'relationshipName' => 'user',
        'ownerKey' => 'id',
    ];

    expect($relationship->toArray())->toBe($expectedArray);
});

test('fromArray creates instance correctly', function () {
    $data = [
        'type' => 'belongs_to',
        'foreignKey' => 'product_id',
        'model' => 'product',
        'currentModelName' => 'orderItem',
        'relationshipName' => 'product',
        'ownerKey' => 'uuid',
    ];

    $primaryKey = Mockery::mock();
    $primaryKey->shouldReceive('getName')->andReturn('id');

    $model = Mockery::mock();
    $model->shouldReceive('getPrimaryKey')->andReturn($primaryKey);

    $registryManager = Mockery::mock(RegistryManager::class);
    $registryManager->shouldReceive('get')
        ->with('model', 'product')
        ->andReturn($model);
    $registryManager->shouldReceive('get')
        ->with('model', 'orderItem')
        ->andReturn($model);

    $relationship = BelongsTo::fromArray($data, $registryManager);

    expect($relationship)->toBeInstanceOf(BelongsTo::class);
    expect($relationship->getType())->toBe(Type::BELONGS_TO);
    expect($relationship->getForeignKey())->toBe('product_id');
    expect($relationship->getRelatedModelName())->toBe('product');
    expect($relationship->getCurrentModelName())->toBe('orderItem');
    expect($relationship->getRelationshipName())->toBe('product');
    expect($relationship->getOwnerKey())->toBe('uuid');
});
