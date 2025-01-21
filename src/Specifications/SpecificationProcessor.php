<?php

namespace Locospec\Engine\Specifications;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\SpecValidator;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private array $pendingRelationships = [];

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->specValidator = new SpecValidator;
    }

    /**
     * Process specifications from a file path
     */
    public function processFile(string $filePath): void
    {
        try {
            if (! file_exists($filePath)) {
                throw new InvalidArgumentException("Specification file not found: {$filePath}");
            }

            $json = file_get_contents($filePath);
            $this->processJson($json);
        } catch (InvalidArgumentException $e) {
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Error processing file {$filePath}: ".$e->getMessage());
        }
    }

    /**
     * Process specifications from a JSON string
     */
    public function processJson(string $json): void
    {
        try {
            $specs = $this->parseJson($json);
            // Phase 1: Process all model spec first
            foreach ($specs as $spec) {
                $this->processModelSpec($spec);
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Error processing JSON: '.$e->getMessage());
        }
    }

    private function parseJson(string $json): array
    {
        $data = json_decode($json, false);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided: '.json_last_error_msg());
        }

        return is_array($data) ? $data : [$data];
    }

    /**
     * Process a single model definition without relationships
     * Todo: change name to processModelSpec
     */
    private function processModelSpec(object $spec): void
    {
        try {
            // Store relationships for later processing
            // Todo: We may not need to set the relationship here
            if (isset($spec->relationships)) {
                $relationshipP = new \stdClass;
                $relationshipP->modelName = $spec->name;
                $relationshipP->relationships = $spec->relationships;

                $this->pendingRelationships[] = $relationshipP;
            }

            // Validate the model spec
            $this->validateModelSpec($spec);

            // Todo: We should normlize the model spec here, maybe change the name here
            $model = ModelDefinition::fromObject($spec);

            // Todo: After normalize, validate the model spec again.
            $this->validateModelSpec($model->toObject());

            $this->registryManager->register($spec->type, $model);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function validateModelSpec(object $spec): void
    {
        $validation = $this->specValidator->validateModel($spec);

        // throw exceptions when validation fails
        if (! $validation['isValid']) {
            foreach ($validation['errors'] as $path => $errors) {
                $errorMessages[] = "$path: ".implode(', ', $errors);
            }
            throw new InvalidArgumentException(
                'Model validation failed: '.implode(', ', $errorMessages)
            );
        }

    }

    /**
     * Process all the relationships after all models are registered
     */
    public function processRelationships(): void
    {
        try {
            foreach ($this->pendingRelationships as $pending) {
                $model = $this->registryManager->get('model', $pending->modelName);

                if (! $model) {
                    throw new InvalidArgumentException(
                        "Cannot process relationships: Model {$pending->modelName} not found"
                    );
                }

                // Use RelationshipProcessor to handle relationship creation
                $relationshipProcessor = new RelationshipProcessor($this->registryManager);

                // Todo: Normalize the relationships and add to the model: Align with the spec with base schema
                $relationshipProcessor->normalizeModelRelationships($model, $pending->relationships);

                // Todo: Validate the model again with the relationship
                $this->validateModelSpec($model->toObject());

                // Todo: Register the model again with the relationship
                $relationshipProcessor->processModelRelationships($model, $pending->relationships);
            }

            // Clear pending relationships after processing
            $this->pendingRelationships = [];
        } catch (InvalidArgumentException $e) {
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Error processing file {$filePath}: ".$e->getMessage());
        }
    }
}
