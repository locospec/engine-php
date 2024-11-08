<?php

namespace Locospec\LCS\Parsers;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Models\ModelDefinition;

class ModelParser implements ParserInterface
{
    public function parseJson(string $json): mixed
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided');
        }

        return $this->parseArray($data);
    }

    public function parseArray(array $data): mixed
    {
        // TODO: Can we use ModelValidator here? Or we completely remove this?
        $this->validateModelData($data);

        return ModelDefinition::fromArray($data);
    }

    private function validateModelData(array $data): void
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Model name is required');
        }

        if (! isset($data['config'])) {
            throw new InvalidArgumentException('Model config is required');
        }

        if (! isset($data['type']) || $data['type'] !== 'model') {
            throw new InvalidArgumentException('Invalid model type');
        }

        if (isset($data['config']) && ! is_array($data['config'])) {
            throw new InvalidArgumentException('Model config must be an array');
        }

        if (isset($data['relationships']) && ! is_array($data['relationships'])) {
            throw new InvalidArgumentException('Model relationships must be an array');
        }
    }
}
