<?php

namespace Locospec\EnginePhp\Models;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Support\StringInflector;

class ModelValidator
{
    private StringInflector $inflector;

    public function __construct()
    {
        $this->inflector = StringInflector::getInstance();
    }

    public static function validate(array $data): void
    {
        $validator = new self;
        $validator->validateStructure($data);
        $validator->validateName($data['name']);
        $validator->validateConfig($data);
        $validator->validateRelationships($data);
    }

    private function validateStructure(array $data): void
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Model name is required');
        }

        if (! isset($data['type']) || $data['type'] !== 'model') {
            throw new InvalidArgumentException('Invalid model type');
        }
    }

    private function validateName(string $name): void
    {
        // Check for empty name
        if (empty($name)) {
            throw new InvalidArgumentException('Model name cannot be empty');
        }

        // Check for spaces
        if (str_contains($name, ' ')) {
            throw new InvalidArgumentException('Model name cannot contain spaces');
        }

        // Check for lowercase only
        if (strtolower($name) !== $name) {
            throw new InvalidArgumentException('Model name must be lowercase');
        }

        // Check for valid separators and characters
        if (! preg_match('/^[a-z]+(?:[-_][a-z]+)*$/', $name)) {
            throw new InvalidArgumentException("Model name: {$name} -  can only contain lowercase letters, hyphens, and underscores");
        }

        // Check if name is singular
        if ($this->inflector->singular($name) !== $name) {
            throw new InvalidArgumentException("Model name: {$name} - must be in singular form");
        }
    }

    private function validateConfig(array $data): void
    {
        if (isset($data['config']) && ! is_array($data['config'])) {
            throw new InvalidArgumentException('Model config must be an array');
        }
    }

    private function validateRelationships(array $data): void
    {
        if (! isset($data['relationships'])) {
            return;
        }

        if (! is_array($data['relationships'])) {
            throw new InvalidArgumentException('Model relationships must be an array');
        }

        foreach ($data['relationships'] as $type => $relations) {
            if (! is_array($relations)) {
                throw new InvalidArgumentException("Relationship type '$type' must be an array");
            }

            foreach ($relations as $name => $config) {
                $this->validateRelationshipConfig($type, $name, $config);
            }
        }
    }

    private function validateRelationshipConfig(string $type, string $name, mixed $config): void
    {
        if (! is_array($config)) {
            return; // Empty config {} is allowed
        }

        if (isset($config['model'])) {
            $this->validateName($config['model']); // Ensure related model names follow same rules
        }

        // Validate foreign/owner keys if present
        foreach (['foreignKey', 'localKey', 'ownerKey'] as $key) {
            if (isset($config[$key]) && ! is_string($config[$key])) {
                throw new InvalidArgumentException("Relationship '$name' $key must be a string");
            }
        }
    }
}
