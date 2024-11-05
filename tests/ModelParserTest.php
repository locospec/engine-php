<?php

namespace Tests\Locospec\EnginePhp\Models;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Models\ModelDefinition;
use Locospec\EnginePhp\Parsers\ModelParser;
use Locospec\EnginePhp\Schema\Schema;

beforeEach(function () {
    $this->parser = new ModelParser;

    // Sample complex model definition with nested schemas
    $this->sampleModelData = [
        'name' => 'profile',
        'type' => 'model',
        'config' => [
            'primaryKey' => 'id',
            'table' => 'profiles',
        ],
        'schema' => [
            'id' => 'ulid',
            'team_id' => 'ulid',
            'pan' => 'string',
            'onboarded_by_id' => 'ulid',
            'onboarded_by_type' => 'string',
            'onboarded' => [
                'type' => 'object',
                'schema' => [
                    'type' => 'string',
                    'next_step' => 'null',
                    'status' => 'string',
                    'steps' => [
                        'type' => 'object',
                        'schema' => [
                            'basic' => [
                                'type' => 'object',
                                'schema' => [
                                    'identifier' => 'string',
                                    'status' => 'string',
                                    'fields' => [
                                        'type' => 'object',
                                        'schema' => [
                                            'email' => 'string',
                                            'mobile_isd' => 'string',
                                            'investor_name' => 'string',
                                            'mobile_number' => 'string',
                                            'investor_is_minor' => 'boolean',
                                            'investor_date_of_birth' => 'date',
                                        ],
                                    ],
                                ],
                            ],
                            'bank' => [
                                'type' => 'object',
                                'schema' => [
                                    'identifier' => 'string',
                                    'status' => 'string',
                                    'fields' => [
                                        'type' => 'object',
                                        'schema' => [
                                            'bank_type' => 'string',
                                            'bank_ifsc_code' => 'string',
                                            'bank_account_number' => 'string',
                                            'bank_primary_account_holder_name' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
            'email' => 'string',
            'mobile' => 'string',
        ],
    ];
});

test('can parse model with nested schemas', function () {
    $model = $this->parser->parseArray($this->sampleModelData);

    // Test basic model properties
    expect($model)->toBeInstanceOf(ModelDefinition::class)
        ->and($model->getName())->toBe('profile')
        ->and($model->getConfig()->getPrimaryKey())->toBe('id')
        ->and($model->getConfig()->getTable())->toBe('profiles');

    // Get schema
    $schema = $model->getSchema();

    // Test top-level attributes
    expect($schema->getProperty('id')->getType())->toBe('ulid')
        ->and($schema->getProperty('team_id')->getType())->toBe('ulid')
        ->and($schema->getProperty('pan')->getType())->toBe('string');

    // Test nested onboarded schema
    $onboardedProp = $schema->getProperty('onboarded');
    expect($onboardedProp->getType())->toBe('object');

    $onboardedSchema = $onboardedProp->getSchema();
    expect($onboardedSchema->getProperty('type')->getType())->toBe('string')
        ->and($onboardedSchema->getProperty('status')->getType())->toBe('string');

    // Test deeply nested steps schema
    $steps = $onboardedSchema->getProperty('steps');
    expect($steps->getType())->toBe('object');

    $stepsSchema = $steps->getSchema();
    expect($stepsSchema->getProperty('basic'))->not->toBeNull();
    expect($stepsSchema->getProperty('bank'))->not->toBeNull();
});

test('can parse nested object fields correctly', function () {
    $model = $this->parser->parseArray($this->sampleModelData);
    $schema = $model->getSchema();

    // Get basic fields schema
    $onboardedSchema = $schema->getProperty('onboarded')->getSchema();
    $stepsSchema = $onboardedSchema->getProperty('steps')->getSchema();
    $basicSchema = $stepsSchema->getProperty('basic')->getSchema();
    $fieldsSchema = $basicSchema->getProperty('fields')->getSchema();

    // Test basic fields
    expect($fieldsSchema->getProperty('email')->getType())->toBe('string')
        ->and($fieldsSchema->getProperty('mobile_isd')->getType())->toBe('string')
        ->and($fieldsSchema->getProperty('investor_is_minor')->getType())->toBe('boolean')
        ->and($fieldsSchema->getProperty('investor_date_of_birth')->getType())->toBe('date');

    // Test bank fields
    $bankSchema = $stepsSchema->getProperty('bank')->getSchema();
    $bankFieldsSchema = $bankSchema->getProperty('fields')->getSchema();
    expect($bankFieldsSchema->getProperty('bank_type')->getType())->toBe('string')
        ->and($bankFieldsSchema->getProperty('bank_ifsc_code')->getType())->toBe('string')
        ->and($bankFieldsSchema->getProperty('bank_account_number')->getType())->toBe('string');
});

test('can convert nested model back to array', function () {
    $model = $this->parser->parseArray($this->sampleModelData);
    $array = $model->toArray();

    // Test structure matches original
    expect($array)->toHaveKey('name', 'profile')
        ->and($array)->toHaveKey('type', 'model')
        ->and($array)->toHaveKey('config')
        ->and($array)->toHaveKey('schema');

    // Test nested schema conversion
    $schema = $model->getSchema()->toShortArray();
    expect($schema)->toHaveKey('onboarded')
        ->and($schema['onboarded'])->toHaveKey('type', 'object')
        ->and($schema['onboarded'])->toHaveKey('schema');

    // Compare with original
    expect($schema)->toEqual($this->sampleModelData['schema']);
});

test('validates required model properties', function () {
    $invalidData = [
        'type' => 'model',
        'schema' => [],
    ];

    expect(fn () => $this->parser->parseArray($invalidData))
        ->toThrow(InvalidArgumentException::class, 'Model name is required');
});

test('can handle null values in schema', function () {
    $model = $this->parser->parseArray($this->sampleModelData);
    $schema = $model->getSchema();

    $onboardedSchema = $schema->getProperty('onboarded')->getSchema();
    expect($onboardedSchema->getProperty('next_step')->getType())->toBe('null');
});

test('can parse schema with timestamps', function () {
    $model = $this->parser->parseArray($this->sampleModelData);
    $schema = $model->getSchema();

    expect($schema->getProperty('created_at')->getType())->toBe('timestamp')
        ->and($schema->getProperty('updated_at')->getType())->toBe('timestamp');
});

test('retains property order when converting back to array', function () {
    $model = $this->parser->parseArray($this->sampleModelData);
    $array = $model->toArray();

    $originalKeys = array_keys($this->sampleModelData['schema']);
    $convertedKeys = array_keys($array['schema']);

    expect($convertedKeys)->toEqual($originalKeys);
});
