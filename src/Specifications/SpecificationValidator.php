<?php

namespace Locospec\LCS\Specifications;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use RuntimeException;

class SpecificationValidator
{
    private Validator $validator;

    private const SCHEMA_BASE_PATH = 'https://locospec.com/schemas';

    private const OPERATION_TYPES = ['insert', 'update', 'delete', 'select'];

    /**
     * Create a new schema validator
     */
    public function __construct()
    {
        $this->validator = new Validator;
        $this->loadSchemas();
    }

    /**
     * Load all required JSON schemas
     */
    private function loadSchemas(): void
    {
        // Register common components schema
        $this->validator->resolver()->registerFile(
            self::SCHEMA_BASE_PATH.'/common.json',
            __DIR__.'/../Specs/common.json'
        );

        // Register common components schema for operations
        $this->validator->resolver()->registerFile(
            self::SCHEMA_BASE_PATH.'/database-operations/common.json',
            __DIR__.'/../Specs/database-operations/common.json'
        );

        // Register individual operation schemas
        foreach (self::OPERATION_TYPES as $type) {
            $this->validator->resolver()->registerFile(
                self::SCHEMA_BASE_PATH."/database-operations/{$type}.json",
                __DIR__.'/../Specs/database-operations/'.$type.'.json'
            );
        }

        // Register model schema
        $this->validator->resolver()->registerFile(
            self::SCHEMA_BASE_PATH.'/model.json',
            __DIR__.'/../Specs/model.json'
        );
    }

    /**
     * Validate a model definition
     *
     * @param  array  $model  The model definition to validate
     * @return array{isValid: bool, errors: array} Validation result and any errors
     */
    public function validateModel(array $model): array
    {
        // Validate against model schema

        $data = Helper::toJSON($model);

        /** @var ValidationResult $result */
        $result = $this->validator->validate(
            $data,
            self::SCHEMA_BASE_PATH.'/model.json'
        );

        if ($result->isValid()) {
            return [
                'isValid' => true,
                'errors' => [],
            ];
        }

        $formatter = new ErrorFormatter;

        return [
            'isValid' => false,
            'errors' => $formatter->format($result->error()),
        ];
    }

    /**
     * Validate a single database operation
     *
     * @param  array  $operation  The operation to validate
     * @return array{isValid: bool, errors: array} Validation result and any errors
     */
    public function validateOperation(array $operation): array
    {
        // First check if operation has a valid type
        if (! isset($operation['type'])) {
            return [
                'isValid' => false,
                'errors' => ['Operation type is required'],
            ];
        }

        $type = $operation['type'];
        if (! in_array($type, self::OPERATION_TYPES)) {
            return [
                'isValid' => false,
                'errors' => ["Invalid operation type: {$type}"],
            ];
        }

        // Validate against specific schema for this operation type
        $data = Helper::toJSON($operation);

        /** @var ValidationResult $result */
        $result = $this->validator->validate(
            $data,
            self::SCHEMA_BASE_PATH."/database-operations/{$type}.json"
        );

        if ($result->isValid()) {

            if ($type === 'select' && isset($operation['pagination']['type']) && $operation['pagination']['type'] === 'cursor') {

                if (! isset($operation['sorts']) || empty($operation['sorts'])) {
                    return [
                        'isValid' => false,
                        'errors' => ['Cursor pagination requires at least one sort to be specified'],
                    ];
                }
            }

            return [
                'isValid' => true,
                'errors' => [],
            ];
        }

        $formatter = new ErrorFormatter;

        return [
            'isValid' => false,
            'errors' => $formatter->format($result->error()),
        ];
    }

    /**
     * Validate common components (filters, sorts, pagination)
     *
     * @param  array  $data  The data to validate
     * @param  string  $component  The component to validate ('filters', 'sorts', 'pagination')
     * @return array{isValid: bool, errors: array}
     */
    public function validateComponent(array $data, string $component): array
    {
        if (! in_array($component, ['filters', 'sorts', 'pagination'])) {
            throw new RuntimeException("Invalid component: {$component}");
        }

        $jsonData = Helper::toJSON($data);

        /** @var ValidationResult $result */
        $result = $this->validator->validate(
            $jsonData,
            self::SCHEMA_BASE_PATH."/database-operations/common.json#/definitions/{$component}"
        );

        if ($result->isValid()) {
            return [
                'isValid' => true,
                'errors' => [],
            ];
        }

        $formatter = new ErrorFormatter;

        return [
            'isValid' => false,
            'errors' => $formatter->format($result->error()),
        ];
    }
}
