<?php

namespace Locospec\Engine\Entities;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Support\StringInflector;

class EntityValidator
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
    }

    private function validateName(string $name): void
    {
        // Check if name is singular
        if ($this->inflector->plural($name) === $name) {
            throw new InvalidArgumentException("Entity name: {$name} - must be in singular form");
        }
    }
}
