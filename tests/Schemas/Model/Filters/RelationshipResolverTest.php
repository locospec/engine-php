<?php

namespace LCSEngine\Tests\Schemas\Model\Filters;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Filters\ComparisonOperator;
use LCSEngine\Schemas\Model\Filters\Filters;
use LCSEngine\Schemas\Model\Filters\LogicalOperator;
use LCSEngine\Schemas\Model\Filters\RelationshipResolver;
use LCSEngine\Schemas\Model\Model;

uses()->group('filters');

beforeEach(function () {
    $this->model = mock(Model::class);
    $this->dbOps = mock(DatabaseOperationsCollection::class);
    $this->registryManager = mock(RegistryManager::class);

    $this->resolver = new RelationshipResolver(
        $this->model,
        $this->dbOps,
        $this->registryManager
    );
});

test('resolve simple condition without relationships', function () {
    $condition = Filters::condition('name', ComparisonOperator::CONTAINS, 'test');
    $filters = new Filters($condition);

    $resolved = $this->resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('name')
        ->and($array['op'])->toBe(ComparisonOperator::CONTAINS->value)
        ->and($array['value'])->toBe('test');
});

test('resolve belongs to relationship condition', function () {
    // Mock the model and its relationships
    $this->model->shouldReceive('getName')->andReturn('posts');

    $userModel = mock(Model::class);
    $userModel->shouldReceive('getName')->andReturn('users');

    $belongsTo = mock(BelongsTo::class);
    $belongsTo->shouldReceive('getRelatedModelName')->andReturn('users');
    $belongsTo->shouldReceive('getOwnerKey')->andReturn('id');
    $belongsTo->shouldReceive('getForeignKey')->andReturn('user_id');

    $this->model->shouldReceive('getRelationship')
        ->with('user')
        ->andReturn($belongsTo);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'posts')
        ->andReturn($this->model);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'users')
        ->andReturn($userModel);

    // Mock database response
    $this->dbOps->shouldReceive('add->execute')
        ->andReturn([
            [
                'result' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
        ]);

    $condition = Filters::condition('user.name', ComparisonOperator::CONTAINS, 'John');
    $filters = new Filters($condition);

    $resolved = $this->resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('user_id')
        ->and($array['op'])->toBe(ComparisonOperator::IS_ANY_OF->value)
        ->and($array['value'])->toBe([1, 2]);
});

test('resolve has many relationship condition', function () {
    // Mock the model and its relationships
    $this->model->shouldReceive('getName')->andReturn('users');

    $postModel = mock(Model::class);
    $postModel->shouldReceive('getName')->andReturn('posts');

    $hasMany = mock(HasMany::class);
    $hasMany->shouldReceive('getRelatedModelName')->andReturn('posts');
    $hasMany->shouldReceive('getForeignKey')->andReturn('user_id');
    $hasMany->shouldReceive('getLocalKey')->andReturn('id');

    $this->model->shouldReceive('getRelationship')
        ->with('posts')
        ->andReturn($hasMany);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'users')
        ->andReturn($this->model);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'posts')
        ->andReturn($postModel);

    // Mock database response
    $this->dbOps->shouldReceive('add->execute')
        ->andReturn([
            [
                'result' => [
                    ['user_id' => 1],
                    ['user_id' => 2],
                ],
            ],
        ]);

    $condition = Filters::condition('posts.title', ComparisonOperator::CONTAINS, 'Test');
    $filters = new Filters($condition);

    $resolved = $this->resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('id')
        ->and($array['op'])->toBe(ComparisonOperator::IS_ANY_OF->value)
        ->and($array['value'])->toBe([1, 2]);
});

test('resolve nested relationship condition', function () {
    // Mock the model and its relationships
    $this->model->shouldReceive('getName')->andReturn('comments');

    $postModel = mock(Model::class);
    $postModel->shouldReceive('getName')->andReturn('posts');

    $userModel = mock(Model::class);
    $userModel->shouldReceive('getName')->andReturn('users');

    $belongsToPost = mock(BelongsTo::class);
    $belongsToPost->shouldReceive('getRelatedModelName')->andReturn('posts');
    $belongsToPost->shouldReceive('getOwnerKey')->andReturn('id');
    $belongsToPost->shouldReceive('getForeignKey')->andReturn('post_id');

    $belongsToUser = mock(BelongsTo::class);
    $belongsToUser->shouldReceive('getRelatedModelName')->andReturn('users');
    $belongsToUser->shouldReceive('getOwnerKey')->andReturn('id');
    $belongsToUser->shouldReceive('getForeignKey')->andReturn('user_id');

    $this->model->shouldReceive('getRelationship')
        ->with('post')
        ->andReturn($belongsToPost);

    $postModel->shouldReceive('getRelationship')
        ->with('user')
        ->andReturn($belongsToUser);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'comments')
        ->andReturn($this->model);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'posts')
        ->andReturn($postModel);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'users')
        ->andReturn($userModel);

    // Mock database responses
    $this->dbOps->shouldReceive('add->execute')
        ->andReturn([
            [
                'result' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
            [
                'result' => [
                    ['user_id' => 3],
                    ['user_id' => 4],
                ],
            ],
        ]);

    $condition = Filters::condition('post.user.name', ComparisonOperator::CONTAINS, 'John');
    $filters = new Filters($condition);

    $resolved = $this->resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['attribute'])->toBe('post_id')
        ->and($array['op'])->toBe(ComparisonOperator::IS_ANY_OF->value)
        ->and($array['value'])->toBe([1, 2]);
});

test('resolve relationship in filter group', function () {
    // Mock the model and its relationships
    $this->model->shouldReceive('getName')->andReturn('posts');

    $userModel = mock(Model::class);
    $userModel->shouldReceive('getName')->andReturn('users');

    $belongsTo = mock(BelongsTo::class);
    $belongsTo->shouldReceive('getRelatedModelName')->andReturn('users');
    $belongsTo->shouldReceive('getOwnerKey')->andReturn('id');
    $belongsTo->shouldReceive('getForeignKey')->andReturn('user_id');

    $this->model->shouldReceive('getRelationship')
        ->with('user')
        ->andReturn($belongsTo);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'posts')
        ->andReturn($this->model);

    $this->registryManager->shouldReceive('get')
        ->with('model', 'users')
        ->andReturn($userModel);

    // Mock database response
    $this->dbOps->shouldReceive('add->execute')
        ->andReturn([
            [
                'result' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
        ]);

    $group = Filters::group(LogicalOperator::AND);
    $group->add(Filters::condition('title', ComparisonOperator::CONTAINS, 'Test'))
        ->add(Filters::condition('user.name', ComparisonOperator::CONTAINS, 'John'));

    $filters = new Filters($group);
    $resolved = $this->resolver->resolve($filters);
    $array = $resolved->toArray();

    expect($array['conditions'])->toHaveCount(2)
        ->and($array['conditions'][0]['attribute'])->toBe('title')
        ->and($array['conditions'][0]['value'])->toBe('Test')
        ->and($array['conditions'][1]['attribute'])->toBe('user_id')
        ->and($array['conditions'][1]['value'])->toBe([1, 2]);
});
