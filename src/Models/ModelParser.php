<?php

namespace Locospec\EnginePhp\Models;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class ModelParser
{
    public function parseJson(string $json): ModelDefinition
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided');
        }

        return $this->parseArray($data);
    }

    public function parseArray(array $data): ModelDefinition
    {
        return ModelDefinition::fromArray($data);
    }
}
