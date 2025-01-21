<?php

namespace Locospec\Engine\Models;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Support\StringInflector;

class ModelValidator
{
    private StringInflector $inflector;

    public function __construct()
    {
        $this->inflector = StringInflector::getInstance();
    }

    public static function validate(object $data): void
    {
        $validator = new self;
        $validator->validateName($data->name);
        $validator->validateConfig($data);
        $validator->validateRelationships($data);
    }

    private function validateName(string $name): void
    {
        // Check if name is singular
        if ($this->inflector->singular($name) !== $name) {
            throw new InvalidArgumentException("Model name: {$name} - must be in singular form");
        }
    }

    private function validateConfig(object $data): void
    {
        if (! isset($data->config)) {
            throw new InvalidArgumentException('Model config not found');
        }
    }

    private function validateRelationships(object $data): void
    {
        if (! isset($data->relationships)) {
            return;
        }

        if (! is_object($data->relationships)) {
            throw new InvalidArgumentException('Model relationships must be an object');
        }

        foreach ($data->relationships as $type => $relations) {
            if (! is_object($relations)) {
                throw new InvalidArgumentException(sprintf(
                    'Relationship type %s must be an object',
                    htmlspecialchars($type, ENT_QUOTES, 'UTF-8')
                ));
            }

            foreach ($relations as $name => $config) {
                $this->validateRelationshipConfig($type, $name, $config);
            }
        }
    }

    private function validateRelationshipConfig(string $type, string $name, mixed $config): void
    {
        if (! is_object($config)) {
            return;
        }

        if (isset($config->model)) {
            $this->validateName($config->model); // Ensure related model names follow same rules
        }

        // Validate foreign/owner keys if present
        foreach (['foreignKey', 'localKey', 'ownerKey'] as $key) {
            if (isset($config->$key) && ! is_string($config->$key)) {
                throw new InvalidArgumentException("Relationship '$name' $key must be a string");
            }
        }
    }
}
