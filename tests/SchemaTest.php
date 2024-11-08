<?php

use Locospec\LCS\Schema\Properties\SchemaPropertyFactory;
use Locospec\LCS\Schema\Properties\SchemaPropertyInterface;
use Locospec\LCS\Schema\Schema;
use Locospec\LCS\Schema\SchemaBuilder;

beforeEach(function () {
    $this->sampleSchema = [
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
                'status' => 'completed',
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
                        'nominees' => [
                            'type' => 'object',
                            'schema' => [
                                'fields' => [
                                    'type' => 'object',
                                    'schema' => [
                                        'skip_nominee' => 'boolean',
                                        'nominees' => [
                                            'type' => 'array',
                                            'schema' => [
                                                'name' => 'string',
                                                'pan' => 'string',
                                            ],
                                        ],
                                    ],
                                ],
                                'status' => 'string',
                                'identifier' => 'string',
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
    ];
});

test('creates schema using builder individually', function () {
    $builder = new SchemaBuilder;

    // Add properties individually since builder methods return SchemaPropertyInterface
    $builder->ulid('id');
    $builder->ulid('team_id');
    $builder->string('pan');

    // Create onboarded object with nested structure
    $onboardedProperty = $builder->object('onboarded');

    $builder->string('email');
    $builder->string('mobile');
    $builder->timestamp('created_at');
    $builder->timestamp('updated_at');

    $schema = $builder->getSchema();

    expect($schema->getProperty('id')->getType())->toBe('ulid')
        ->and($schema->getProperty('team_id')->getType())->toBe('ulid')
        ->and($schema->getProperty('pan')->getType())->toBe('string')
        ->and($schema->getProperty('onboarded')->getType())->toBe('object')
        ->and($schema->getProperty('created_at')->getType())->toBe('timestamp')
        ->and($schema->getProperty('updated_at')->getType())->toBe('timestamp');
});

test('builds complex nested schema using builder', function () {
    $builder = new SchemaBuilder;

    // Create main properties
    $builder->ulid('id');
    $builder->string('pan');

    // Create onboarded object
    $onboardedProp = $builder->object('onboarded');

    // Create a new SchemaBuilder for onboarded schema
    $onboardedBuilder = new SchemaBuilder;
    $onboardedBuilder->string('type');
    $onboardedBuilder->string('status');

    // Create steps object in onboarded
    $stepsProp = $onboardedBuilder->object('steps');

    // Create basic step schema
    $basicBuilder = new SchemaBuilder;
    $basicBuilder->string('identifier');
    $basicBuilder->string('status');

    // Create fields for basic step
    $fieldsBuilder = new SchemaBuilder;
    $fieldsBuilder->string('email');
    $fieldsBuilder->string('mobile_isd');
    $fieldsBuilder->boolean('investor_is_minor');
    $fieldsBuilder->date('investor_date_of_birth');

    // Get the completed schema
    $schema = $builder->getSchema();

    // Test the structure
    expect($schema->getProperty('id')->getType())->toBe('ulid')
        ->and($schema->getProperty('pan')->getType())->toBe('string')
        ->and($schema->getProperty('onboarded')->getType())->toBe('object');
});

test('creates complex nested schema directly using Schema class', function () {
    $schema = new Schema;

    // Add basic properties
    $schema->addProperty('id', SchemaPropertyFactory::create('ulid'));
    $schema->addProperty('pan', SchemaPropertyFactory::create('string'));

    // Create onboarded object with its own schema
    $onboardedSchema = new Schema;
    $onboardedSchema->addProperty('type', SchemaPropertyFactory::create('string'));
    $onboardedSchema->addProperty('status', SchemaPropertyFactory::create('string'));

    // Create and add the onboarded property
    $onboardedProp = SchemaPropertyFactory::create('object');
    $onboardedProp->setSchema($onboardedSchema);
    $schema->addProperty('onboarded', $onboardedProp);

    // Test the structure
    expect($schema->getProperty('id')->getType())->toBe('ulid')
        ->and($schema->getProperty('pan')->getType())->toBe('string')
        ->and($schema->getProperty('onboarded'))->toBeInstanceOf(SchemaPropertyInterface::class)
        ->and($schema->getProperty('onboarded')->getType())->toBe('object')
        ->and($schema->getProperty('onboarded')->getSchema())->toBeInstanceOf(Schema::class);

    // Test nested schema
    $nestedSchema = $schema->getProperty('onboarded')->getSchema();
    expect($nestedSchema->getProperty('type')->getType())->toBe('string')
        ->and($nestedSchema->getProperty('status')->getType())->toBe('string');
});

test('creates nominees schema structure', function () {
    // Create nominees schema structure
    $nomineesSchema = new Schema;

    // Create fields schema
    $fieldsSchema = new Schema;
    $fieldsSchema->addProperty('skip_nominee', SchemaPropertyFactory::create('boolean'));

    // Create nominees array property with its schema
    $nomineesArraySchema = new Schema;
    $nomineesArraySchema->addProperty('name', SchemaPropertyFactory::create('string'));
    $nomineesArraySchema->addProperty('pan', SchemaPropertyFactory::create('string'));

    $nomineesArrayProp = SchemaPropertyFactory::create('array');
    $nomineesArrayProp->setSchema($nomineesArraySchema);
    $fieldsSchema->addProperty('nominees', $nomineesArrayProp);

    // Add fields to nominees schema
    $fieldsProp = SchemaPropertyFactory::create('object');
    $fieldsProp->setSchema($fieldsSchema);

    $nomineesSchema->addProperty('fields', $fieldsProp);
    $nomineesSchema->addProperty('status', SchemaPropertyFactory::create('string'));
    $nomineesSchema->addProperty('identifier', SchemaPropertyFactory::create('string'));

    // Verify the structure
    expect($nomineesSchema->getProperty('fields')->getType())->toBe('object')
        ->and($nomineesSchema->getProperty('status')->getType())->toBe('string')
        ->and($nomineesSchema->getProperty('identifier')->getType())->toBe('string');

    // Verify fields structure
    $fields = $nomineesSchema->getProperty('fields')->getSchema();
    expect($fields->getProperty('skip_nominee')->getType())->toBe('boolean')
        ->and($fields->getProperty('nominees')->getType())->toBe('array');

    // Verify nominees array schema
    $nomineesItems = $fields->getProperty('nominees')->getSchema();
    expect($nomineesItems->getProperty('name')->getType())->toBe('string')
        ->and($nomineesItems->getProperty('pan')->getType())->toBe('string');
});

test('creates schema from array and converts back', function () {
    $inputArray = [
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => [
                            'type' => 'boolean',
                        ],
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'pan' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => [
                    'type' => 'string',
                ],
                'identifier' => [
                    'type' => 'string',
                ],
            ],
        ],
    ];

    // Test fromArray
    $schema = Schema::fromArray([
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => 'boolean',
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => 'string',
                                'pan' => 'string',
                            ],
                        ],
                    ],
                ],
                'status' => 'string',
                'identifier' => 'string',
            ],
        ],
    ]);

    // Test toArray
    $outputArray = $schema->toArray();
    expect($outputArray)->toBe($inputArray);
});

test('converts schema to JSON', function () {
    $inputArray = [
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => [
                            'type' => 'boolean',
                        ],
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'pan' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => [
                    'type' => 'string',
                ],
                'identifier' => [
                    'type' => 'string',
                ],
            ],
        ],
    ];

    // Create schema from shorthand format
    $schema = Schema::fromArray([
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => 'boolean',
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => 'string',
                                'pan' => 'string',
                            ],
                        ],
                    ],
                ],
                'status' => 'string',
                'identifier' => 'string',
            ],
        ],
    ]);

    // Test toJson
    $json = $schema->toJson();
    expect($json)->toBe(json_encode($inputArray, JSON_PRETTY_PRINT))
        ->and(json_decode($json, true))->toBe($inputArray);
});

test('handles empty schema serialization', function () {
    $schema = new Schema;

    expect($schema->toArray())->toBe([])
        ->and($schema->toJson())->toBe('[]');
});

test('maintains schema title and description in serialization', function () {
    $schema = new Schema('Nominees Schema', 'Schema for nominee information');
    $inputArray = [
        'skip_nominee' => 'boolean',
        'nominees' => [
            'type' => 'array',
            'schema' => [
                'name' => 'string',
                'pan' => 'string',
            ],
        ],
    ];

    foreach ($inputArray as $key => $value) {
        if (is_string($value)) {
            $schema->addProperty($key, SchemaPropertyFactory::create($value));
        }
    }

    $output = $schema->toArray();
    expect($output)->toHaveKey('title', 'Nominees Schema')
        ->toHaveKey('description', 'Schema for nominee information');

    $json = $schema->toJson();
    $decodedJson = json_decode($json, true);
    expect($decodedJson)->toHaveKey('title', 'Nominees Schema')
        ->toHaveKey('description', 'Schema for nominee information');
});

test('creates schema from long array and converts back', function () {
    $inputArray = [
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => [
                            'type' => 'boolean',
                        ],
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'pan' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => [
                    'type' => 'string',
                ],
                'identifier' => [
                    'type' => 'string',
                ],
            ],
        ],
    ];

    // Test fromArray
    $schema = Schema::fromArray($inputArray);

    // Test toArray
    $outputArray = $schema->toArray();

    $json = $schema->toJson();

    dump(json_decode($json, true));

    expect($outputArray)->toBe($inputArray);
});

test('converts schema to short array format', function () {
    $inputArray = [
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => 'boolean',
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => 'string',
                                'pan' => 'string',
                            ],
                        ],
                    ],
                ],
                'status' => 'string',
                'identifier' => 'string',
            ],
        ],
    ];

    $schema = Schema::fromArray($inputArray);

    // The regular toArray() will have the full format
    $fullArray = $schema->toArray();
    expect($fullArray['nominees']['schema']['fields']['schema']['skip_nominee'])->toBe(['type' => 'boolean']);

    // The toShortArray() will have the shortened format
    $shortArray = $schema->toShortArray();
    expect($shortArray)->toBe($inputArray)
        ->and($shortArray['nominees']['schema']['fields']['schema']['skip_nominee'])->toBe('boolean')
        ->and($shortArray['nominees']['schema']['status'])->toBe('string')
        ->and($shortArray['nominees']['schema']['fields']['schema']['nominees']['schema']['name'])->toBe('string');
});

test('handles mixed format input and converts to both full and short formats', function () {
    // Input with mixed formats (both full and short property declarations)
    $inputArray = [
        'id' => 'ulid', // short format
        'name' => [ // full format
            'type' => 'string',
        ],
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => 'boolean', // short format
                        'is_verified' => [ // full format
                            'type' => 'boolean',
                        ],
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => 'string', // short format
                                'pan' => [ // full format
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => 'string', // short format
                'identifier' => [ // full format
                    'type' => 'string',
                ],
            ],
        ],
    ];

    $schema = Schema::fromArray($inputArray);

    // Expected full format output
    $expectedFullArray = [
        'id' => [
            'type' => 'ulid',
        ],
        'name' => [
            'type' => 'string',
        ],
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => [
                            'type' => 'boolean',
                        ],
                        'is_verified' => [
                            'type' => 'boolean',
                        ],
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'pan' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => [
                    'type' => 'string',
                ],
                'identifier' => [
                    'type' => 'string',
                ],
            ],
        ],
    ];

    // Expected short format output
    $expectedShortArray = [
        'id' => 'ulid',
        'name' => 'string',
        'nominees' => [
            'type' => 'object',
            'schema' => [
                'fields' => [
                    'type' => 'object',
                    'schema' => [
                        'skip_nominee' => 'boolean',
                        'is_verified' => 'boolean',
                        'nominees' => [
                            'type' => 'array',
                            'schema' => [
                                'name' => 'string',
                                'pan' => 'string',
                            ],
                        ],
                    ],
                ],
                'status' => 'string',
                'identifier' => 'string',
            ],
        ],
    ];

    // Test both formats
    expect($schema->toArray())->toBe($expectedFullArray)
        ->and($schema->toShortArray())->toBe($expectedShortArray);
});
