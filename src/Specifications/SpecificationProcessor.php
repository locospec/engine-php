<?php

namespace Locospec\Engine\Specifications;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\SpecValidator;
use Locospec\Engine\LCS;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private array $pendingRelationships = [];

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->specValidator = new SpecValidator;
        $this->logger = LCS::getLogger();
        
        $this->logger?->info('SpecificationProcessor initialized');
    }

    /**
     * Process specifications from a file path
     */
    public function processFile(string $filePath): void
    {
        try {
            $this->logger?->info('Processing spec file', ['filePath' => $filePath]);

            if (! file_exists($filePath)) {
                $this->logger?->error('Spec json file not found', ['filePath' => $filePath]);
                throw new InvalidArgumentException("Specification file not found: {$filePath}");
            }

            $json = file_get_contents($filePath);
            $this->processJson($json);
            
            $this->logger?->info('Successfully processed spec file', ['filePath' => $filePath]);
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during spec file processing', [
                'filePath' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing spec file', [
                'filePath' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new InvalidArgumentException("Error processing file {$filePath}: ".$e->getMessage());
        }
    }

    /**
     * Process specifications from a JSON string
     */
    public function processJson(string $json): void
    {
        try {
            $this->logger?->info('Processing JSON spec');
            
            $specs = $this->parseJson($json);

            foreach ($specs as $spec) {
                $this->processModelSpec($spec);
            }

            $this->logger?->info('Successfully processed JSON spec');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during JSON processing', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing JSON', [
                'error' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Error processing JSON: '.$e->getMessage());
        }
    }

    private function parseJson(string $json): array
    {
        $this->logger?->info('Parsing JSON data');
        $data = json_decode($json, false);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger?->error('Invalid JSON provided', ['error' => json_last_error_msg()]);
            throw new InvalidArgumentException('Invalid JSON provided: '.json_last_error_msg());
        }

        $this->logger?->info('Successfully parsed JSON data');
        return is_array($data) ? $data : [$data];
    }

    /**
     * Process a single model definition without relationships
     */
    private function processModelSpec(object $spec): void
    {
        try {
            $this->logger?->info('Processing model spec', ['modelName' => $spec->name]);
            // Store relationships for later processing
            if (isset($spec->relationships)) {
                $relationshipP = new \stdClass;
                $relationshipP->modelName = $spec->name;
                $relationshipP->relationships = $spec->relationships;

                $this->pendingRelationships[] = $relationshipP;
                $this->logger?->info('Stored pending relationships', ['modelName' => $spec->name]);
            }

            // Validate the model spec
            $this->validateModelSpec($spec);
            
            // Convert object to ModelDefinition
            $model = ModelDefinition::fromObject($spec);
            $this->logger?->info('Normalized model spec', ['modelName' => $model->getName()]);

            // Revalidate after conversion
            $this->validateModelSpec($model->toObject());

            $this->registryManager->register($spec->type, $model);
            $this->logger?->info('Model registered in registry', ['modelName' => $model->getName()]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing model spec', [
                'modelName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function validateModelSpec(object $spec): void
    {
        $this->logger?->info('Validating model spec', ['modelName' => $spec->name]);
        $validation = $this->specValidator->validateModel($spec);

        // throw exceptions when validation fails
        if (! $validation['isValid']) {
            foreach ($validation['errors'] as $path => $errors) {
                $errorMessages[] = "$path: ".implode(', ', $errors);
            }

            $this->logger?->error('Model validation failed', [
                'modelName' => $spec->name,
                'errors' => $errorMessages
            ]);

            throw new InvalidArgumentException(
                'Model validation failed: '.implode(', ', $errorMessages)
            );
        }
        $this->logger?->info('Model spec validated successfully', ['modelName' => $spec->name]);
    }

    /**
     * Process all the relationships after all models are registered
     */
    public function processRelationships(): void
    {
        try {
            $this->logger?->info('Processing relationships for models');

            foreach ($this->pendingRelationships as $pending) {
                $this->logger?->info('Processing relationships for model', ['modelName' => $pending->modelName]);
                $model = $this->registryManager->get('model', $pending->modelName);

                if (! $model) {
                    $this->logger?->error('Model not found for relationship processing', [
                        'modelName' => $pending->modelName
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process relationships: Model {$pending->modelName} not found"
                    );
                }

                // Use RelationshipProcessor to handle relationship creation
                $relationshipProcessor = new RelationshipProcessor($this->registryManager);

                // Normalize and validate relationships
                $relationshipProcessor->normalizeModelRelationships($model, $pending->relationships);
                $this->validateModelSpec($model->toObject());

                // Register relationships
                $relationshipProcessor->processModelRelationships($model, $pending->relationships);
                
                $this->logger?->info('Successfully processed relationships for model', ['modelName' => $pending->modelName]);
            }

            // Clear pending relationships after processing
            $this->pendingRelationships = [];
            $this->logger?->info('Successfully processed relationships for all the models');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during relationship processing', [
                'error' => $e->getMessage()
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing relationships', [
                'error' => $e->getMessage()
            ]);
            throw new InvalidArgumentException("Error processing file {$filePath}: ".$e->getMessage());
        }
    }
}
