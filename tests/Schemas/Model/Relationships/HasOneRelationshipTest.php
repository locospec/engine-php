<?php

use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Type;

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
        'relatedModelName' => 'Address',
        'currentModelName' => 'Order',
        'relationshipName' => 'shippingAddress',
        'localKey' => 'uuid',
    ];

    $relationship = HasOne::fromArray($data);

    expect($relationship)->toBeInstanceOf(HasOne::class);
    expect($relationship->getType())->toBe(Type::HAS_ONE);
    expect($relationship->getForeignKey())->toBe('address_id');
    expect($relationship->getRelatedModelName())->toBe('Address');
    expect($relationship->getCurrentModelName())->toBe('Order');
    expect($relationship->getRelationshipName())->toBe('shippingAddress');
    expect($relationship->getLocalKey())->toBe('uuid');
});
