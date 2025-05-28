<?php

namespace LCSEngine\Views;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Support\StringInflector;

class ViewValidator
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
    }

    private function validateStructure(array $data): void
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('View name is required');
        }

        if (! isset($data['type']) || $data['type'] !== 'view') {
            throw new InvalidArgumentException('Invalid model type');
        }
    }

    private function validateName(string $name): void
    {
        // Check for empty name
        if (empty($name)) {
            throw new InvalidArgumentException('View name cannot be empty');
        }

        // Check for spaces
        if (str_contains($name, ' ')) {
            throw new InvalidArgumentException('View name cannot contain spaces');
        }

        // Check for lowercase only
        if (strtolower($name) !== $name) {
            throw new InvalidArgumentException('View name must be lowercase');
        }

        // Check for valid separators and characters
        if (! preg_match('/^[a-z]+(?:[-_][a-z]+)*$/', $name)) {
            throw new InvalidArgumentException("View name: {$name} -  can only contain lowercase letters, hyphens, and underscores");
        }

        // Check if name is singular
        if ($this->inflector->plural($name) === $name) {
            throw new InvalidArgumentException("View name: {$name} - must be in singular form");
        }
    }
}
