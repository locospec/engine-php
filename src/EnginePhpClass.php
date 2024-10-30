<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Models\ModelParser;
use Locospec\EnginePhp\Models\ModelRegistry;
use Locospec\EnginePhp\Models\ModelDefinition;

class EnginePhpClass
{
    private ModelRegistry $modelRegistry;
    private ModelParser $modelParser;

    public function __construct()
    {
        $this->modelRegistry = ModelRegistry::getInstance();
        $this->modelParser = new ModelParser();
    }

    /**
     * Process specifications from a JSON file path
     *
     * @param string $filePath Path to the JSON specification file
     * @throws InvalidArgumentException If file doesn't exist or contains invalid JSON
     * @return void
     */
    public function processSpecificationFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Specification file not found: {$filePath}");
        }

        $json = file_get_contents($filePath);
        $this->processSpecificationJson($json);
    }

    /**
     * Process specifications directly from a JSON string
     *
     * @param string $json JSON string containing specifications
     * @throws InvalidArgumentException If JSON is invalid
     * @return void
     */
    public function processSpecificationJson(string $json): void
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
        }

        // Handle both single object and array of objects
        $specs = is_array($data) && !isset($data['type']) ? $data : [$data];

        foreach ($specs as $spec) {
            $this->processSpecification($spec);
        }
    }

    /**
     * Process a single specification
     *
     * @param array $spec Specification array with 'type' field
     * @throws InvalidArgumentException If specification type is invalid or missing
     * @return void
     */
    private function processSpecification(array $spec): void
    {
        if (!isset($spec['type'])) {
            throw new InvalidArgumentException('Specification must include a type');
        }

        switch ($spec['type']) {
            case 'model':
                $model = $this->modelParser->parseArray($spec);
                $this->modelRegistry->register($model);
                break;
                // Future registry types can be added here
                // case 'view':
                //     $this->viewRegistry->register($this->viewParser->parseArray($spec));
                //     break;
            default:
                throw new InvalidArgumentException("Unsupported specification type: {$spec['type']}");
        }
    }

    /**
     * Get a model by name
     *
     * @param string $name Model name
     * @return ModelDefinition|null
     */
    public function getModel(string $name): ?ModelDefinition
    {
        return $this->modelRegistry->get($name);
    }

    /**
     * Check if a model exists
     *
     * @param string $name Model name
     * @return bool
     */
    public function hasModel(string $name): bool
    {
        return $this->modelRegistry->has($name);
    }

    /**
     * Get all registered models
     *
     * @return array<string, ModelDefinition>
     */
    public function getAllModels(): array
    {
        return $this->modelRegistry->all();
    }

    /**
     * Get the model registry instance
     *
     * @return ModelRegistry
     */
    public function getModelRegistry(): ModelRegistry
    {
        return $this->modelRegistry;
    }
}
