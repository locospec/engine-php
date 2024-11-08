<?php

namespace Locospec\LCS\Specifications;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Parsers\ParserFactory;
use Locospec\LCS\Registry\RegistryManager;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private ParserFactory $parserFactory;

    private array $pendingRelationships = [];

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->parserFactory = new ParserFactory;
    }

    /**
     * Process specifications from a file path
     */
    public function processFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Specification file not found: {$filePath}");
        }

        $json = file_get_contents($filePath);
        $this->processJson($json);
    }

    /**
     * Process specifications from a JSON string
     */
    public function processJson(string $json): void
    {
        $data = $this->parseJson($json);
        $specs = $this->normalizeSpecifications($data);

        // Phase 1: Register all models first
        foreach ($specs as $spec) {
            $this->processModelDefinition($spec);
        }

        // Phase 2: Process all relationships after models are registered
        $this->processAllPendingRelationships();
    }

    private function parseJson(string $json): array
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided: '.json_last_error_msg());
        }

        return $data;
    }

    private function normalizeSpecifications(array $data): array
    {
        return is_array($data) && ! isset($data['type']) ? $data : [$data];
    }

    /**
     * Process a single model definition without relationships
     */
    private function processModelDefinition(array $spec): void
    {
        if (! isset($spec['type'])) {
            throw new InvalidArgumentException('Specification must include a type');
        }

        if ($spec['type'] !== 'model') {
            throw new InvalidArgumentException('Only model specifications are supported');
        }

        // Store relationships for later processing
        if (isset($spec['relationships'])) {
            $this->pendingRelationships[] = [
                'modelName' => $spec['name'],
                'relationships' => $spec['relationships'],
            ];

            // Remove relationships before parsing model
            unset($spec['relationships']);
        }

        // Parse and register the model
        $parser = $this->parserFactory->createParser($spec['type']);
        $model = $parser->parseArray($spec);
        $this->registryManager->register($spec['type'], $model);
    }

    /**
     * Process all pending relationships after all models are registered
     */
    private function processAllPendingRelationships(): void
    {
        foreach ($this->pendingRelationships as $pending) {
            $model = $this->registryManager->get('model', $pending['modelName']);

            if (! $model) {
                throw new InvalidArgumentException(
                    "Cannot process relationships: Model {$pending['modelName']} not found"
                );
            }

            // Use RelationshipProcessor to handle relationship creation
            $relationshipProcessor = new RelationshipProcessor($this->registryManager);
            $relationshipProcessor->processModelRelationships($model, $pending['relationships']);
        }

        // Clear pending relationships after processing
        $this->pendingRelationships = [];
    }
}
