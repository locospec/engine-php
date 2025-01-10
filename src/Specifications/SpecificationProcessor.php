<?php

namespace Locospec\LCS\Specifications;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Registry\RegistryManager;
use Locospec\LCS\Models\ModelDefinition;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private array $pendingRelationships = [];

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->modelSpecValidator = new SpecificationValidator;
    }

    /**
     * Process specifications from a file path
     */
    public function processFile(string $filePath): void
    {
        try{
            if (! file_exists($filePath)) {
                throw new InvalidArgumentException("Specification file not found: {$filePath}");
            }

            $json = file_get_contents($filePath);
            $this->processJson($json);
        } catch (InvalidArgumentException $e) {
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Error processing file {$filePath}: " . $e->getMessage());
        }
    }

    /**
     * Process specifications from a JSON string
     */
    public function processJson(string $json): void
    {
        try{
            $data = $this->parseJson($json);
            $specs = $this->normalizeSpecifications($data);
            // Phase 1: Register all models first
            foreach ($specs as $spec) {
                $this->processModelDefinition($spec);
            }

            // Phase 2: Process all relationships after models are registered
            $this->processAllPendingRelationships();
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Error processing JSON: " . $e->getMessage());
        }
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
        // Store relationships for later processing
        if (isset($spec['relationships'])) {
            $this->pendingRelationships[] = [
                'modelName' => $spec['name'],
                'relationships' => $spec['relationships'],
            ];
        }

        // Validate and register the model
        $validation = $this->modelSpecValidator->validateModel($spec);
        
        // throw exceptions when validation fails
        if(!$validation['isValid']){
            foreach ($validation['errors'] as $path => $errors) {
                $errorMessages[] =  "$path: " . implode(', ', $errors);
            }
            throw new InvalidArgumentException(
                "Model validation failed: " . implode(", ", $errorMessages)
            );
        }
        
        $model = ModelDefinition::fromArray($spec);
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
