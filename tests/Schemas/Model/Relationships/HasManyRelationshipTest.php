<?php

use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\Type;
use Mockery;

uses()->group('relationships');

test('can set and get properties for HasManyRelationship', function () {
    $relationship = new HasMany;

    $relationship->setType(Type::HAS_MANY);
    $relationship->setForeignKey('post_id');
    $relationship->setRelatedModelName('Post');
    $relationship->setCurrentModelName('User');
    $relationship->setRelationshipName('posts');
    $relationship->setLocalKey('id');

    expect($relationship->getType())->toBe(Type::HAS_MANY);
    expect($relationship->getForeignKey())->toBe('post_id');
    expect($relationship->getRelatedModelName())->toBe('Post');
    expect($relationship->getCurrentModelName())->toBe('User');
    expect($relationship->getRelationshipName())->toBe('posts');
    expect($relationship->getLocalKey())->toBe('id');
});

test('toArray returns correct array structure for HasManyRelationship', function () {
    $relationship = new HasMany;

    $relationship->setType(Type::HAS_MANY);
    $relationship->setForeignKey('post_id');
    $relationship->setRelatedModelName('Post');
    $relationship->setCurrentModelName('User');
    $relationship->setRelationshipName('posts');
    $relationship->setLocalKey('id');

    $expectedArray = [
        'type' => 'has_many',
        'foreignKey' => 'post_id',
        'relatedModelName' => 'Post',
        'currentModelName' => 'User',
        'relationshipName' => 'posts',
        'localKey' => 'id',
    ];

    expect($relationship->toArray())->toBe($expectedArray);
});

test('fromArray creates instance correctly for HasManyRelationship', function () {
    $data = [
        'type' => 'has_many',
        'foreignKey' => 'order_id',
        'model' => 'orderItem',
        'currentModelName' => 'order',
        'relationshipName' => 'items',
        'localKey' => 'uuid',
    ];

    $primaryKey = Mockery::mock();
    $primaryKey->shouldReceive('getName')->andReturn('id');

    $model = Mockery::mock();
    $model->shouldReceive('getPrimaryKey')->andReturn($primaryKey);

    $registryManager = Mockery::mock(RegistryManager::class);
    $registryManager->shouldReceive('get')
        ->with('model', 'order')
        ->andReturn($model);
    $registryManager->shouldReceive('get')
        ->with('model', 'orderItem')
        ->andReturn($model);

    $relationship = HasMany::fromArray($data, $registryManager);

    expect($relationship)->toBeInstanceOf(HasMany::class);
    expect($relationship->getType())->toBe(Type::HAS_MANY);
    expect($relationship->getForeignKey())->toBe('order_id');
    expect($relationship->getRelatedModelName())->toBe('orderItem');
    expect($relationship->getCurrentModelName())->toBe('order');
    expect($relationship->getRelationshipName())->toBe('items');
    expect($relationship->getLocalKey())->toBe('uuid');
});
