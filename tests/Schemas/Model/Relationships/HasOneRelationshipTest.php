<?php

use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Type;
use Mockery;

uses()->group('relationships');

test('can set and get properties for HasOneRelationship', function () {
    $relationship = new HasOne;

    $relationship->setType(Type::HAS_ONE);
    $relationship->setForeignKey('user_id');
    $relationship->setRelatedModelName('User');
    $relationship->setCurrentModelName('Profile');
    $relationship->setRelationshipName('user');
    $relationship->setLocalKey('id');

    expect($relationship->getType())->toBe(Type::HAS_ONE);
    expect($relationship->getForeignKey())->toBe('user_id');
    expect($relationship->getRelatedModelName())->toBe('User');
    expect($relationship->getCurrentModelName())->toBe('Profile');
    expect($relationship->getRelationshipName())->toBe('user');
    expect($relationship->getLocalKey())->toBe('id');
});

test('toArray returns correct array structure for HasOneRelationship', function () {
    $relationship = new HasOne;

    $relationship->setType(Type::HAS_ONE);
    $relationship->setForeignKey('user_id');
    $relationship->setRelatedModelName('User');
    $relationship->setCurrentModelName('Profile');
    $relationship->setRelationshipName('user');
    $relationship->setLocalKey('id');

    $expectedArray = [
        'type' => 'has_one',
        'foreignKey' => 'user_id',
        'relatedModelName' => 'User',
        'currentModelName' => 'Profile',
        'relationshipName' => 'user',
        'localKey' => 'id',
    ];

    expect($relationship->toArray())->toBe($expectedArray);
});

test('fromArray creates instance correctly for HasOneRelationship', function () {
    $data = [
        'type' => 'has_one',
        'foreignKey' => 'address_id',
        'model' => 'address',
        'currentModelName' => 'order',
        'relationshipName' => 'shippingAddress',
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
        ->with('model', 'address')
        ->andReturn($model);

    $relationship = HasOne::fromArray($data, $registryManager);

    expect($relationship)->toBeInstanceOf(HasOne::class);
    expect($relationship->getType())->toBe(Type::HAS_ONE);
    expect($relationship->getForeignKey())->toBe('address_id');
    expect($relationship->getRelatedModelName())->toBe('address');
    expect($relationship->getCurrentModelName())->toBe('order');
    expect($relationship->getRelationshipName())->toBe('shippingAddress');
    expect($relationship->getLocalKey())->toBe('uuid');
});
