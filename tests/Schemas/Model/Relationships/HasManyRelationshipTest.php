<?php

use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\Type;

uses()->group('relationships');

test('can set and get properties for HasManyRelationship', function () {
    $relationship = new HasMany();

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
    $relationship = new HasMany();

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
        'relatedModelName' => 'OrderItem',
        'currentModelName' => 'Order',
        'relationshipName' => 'items',
        'localKey' => 'uuid',
    ];

    $relationship = HasMany::fromArray($data);

    expect($relationship)->toBeInstanceOf(HasMany::class);
    expect($relationship->getType())->toBe(Type::HAS_MANY);
    expect($relationship->getForeignKey())->toBe('order_id');
    expect($relationship->getRelatedModelName())->toBe('OrderItem');
    expect($relationship->getCurrentModelName())->toBe('Order');
    expect($relationship->getRelationshipName())->toBe('items');
    expect($relationship->getLocalKey())->toBe('uuid');
}); 