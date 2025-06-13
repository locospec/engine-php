<?php

namespace LCSEngine\Specifications;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
use LCSEngine\Logger;
use LCSEngine\Mutators\MutatorDefinition;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\SpecValidator;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Query\Query;

class SpecificationProcessor
{
    private RegistryManager $registryManager;
    private SpecValidator $specValidator;
    private ?Logger $logger = null;
    private array $pendingRelationships = [];
    private array $pendingQueries = [];
    private array $pendingMutators = [];

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
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing spec file', [
                'filePath' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException("Error processing file {$filePath}: " . $e->getMessage());
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
                switch ($spec['type']) {
                    case 'model':
                        $this->processModel($spec);
                        break;

                    case 'query':
                        $this->pendingQueries[] = $spec;
                        break;

                    case 'mutator':
                        $this->pendingMutators[] = json_decode($json, false);
                        break;

                    default:
                        break;
                }
            }

            $this->logger?->info('Successfully processed JSON spec');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during JSON processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing JSON', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing JSON: ' . $e->getMessage());
        }
    }

    private function parseJson(string $json): array
    {
        $this->logger?->info('Parsing JSON data');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger?->error('Invalid JSON provided', ['error' => json_last_error_msg()]);
            throw new InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
        }

        $this->logger?->info('Successfully parsed JSON data');

        return array_is_list($data) ? $data : [$data];
    }

    // this can be removed
    public function validateSpec(object $spec): void
    {
        $this->logger?->info('Validating ' . $spec->type . ' spec', [$spec->type . 'Name' => $spec->name]);
        $validation = $this->specValidator->validateSpec($spec);

        // throw exceptions when validation fails
        if (! $validation['isValid']) {
            foreach ($validation['errors'] as $path => $errors) {
                $errorMessages[] = "$path: " . implode(', ', $errors);
            }

            $this->logger?->error($spec->type . ' validation failed', [
                $spec->type . 'Name' => $spec->name,
                'errors' => $errorMessages,
            ]);

            throw new InvalidArgumentException(
                $spec->type . ' validation failed: ' . implode(', ', $errorMessages)
            );
        }
        $this->logger?->info($spec->type . ' spec validated successfully', [$spec->type . 'Name' => $spec->name]);
    }

    /**
     * Process a all mutators definition
     */
    public function processAllMutatorsSpec(): void
    {
        try {
            $this->logger?->info('Processing all the mutators');
            foreach ($this->pendingMutators as $pending) {
                $this->logger?->info('Processing mutator', ['mutatorName' => $pending->name]);
                $model = $this->registryManager->get('model', $pending->model);

                if (! $model) {
                    $this->logger?->error('Model not found for mutator processing', [
                        'modelName' => $pending->model,
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process mutator: Model {$pending->model} not found"
                    );
                }

                $this->processMutatorSpec($pending, $model);
            }
            // Clear pending mutators after processing
            $this->pendingMutators = [];
            $this->logger?->info('Successfully processed all the mutators');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during mutator processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing mutator', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing mutator: ' . $e->getMessage());
        }
    }

    /**
     * Process a single mutator definition
     */
    private function processMutatorSpec(object $spec, Model $model): void
    {
        try {
            $this->logger?->info('Processing mutator spec', ['mutatorSpecName' => $spec->name]);

            // Validate the mutator spec
            $this->validateSpec($spec);
            // Convert object to MutatorDefinition
            $mutatorSpec = MutatorDefinition::fromObject($spec, $this->registryManager, $model);

            $this->logger?->info('Normalized mutator spec', ['mutatorSpecName' => $mutatorSpec->getName()]);

            // register the mutator
            $this->registryManager->register('mutator', $mutatorSpec);
            $this->logger?->info('Mutator registered in registry', ['modelName' => $mutatorSpec->getName()]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing mutator spec', [
                'mutatorName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function processModel(array $spec): void
    {
        try {
            $this->logger?->info('Processing model', ['modelName' => $spec['name']]);
            // Store relationships for later processing
            if (isset($spec['relationships'])) {
                $relationshipP = [
                    'modelName' => $spec['name'],
                    'relationships' => $spec['relationships']
                ];

                $this->pendingRelationships[] = $relationshipP;
                unset($spec['relationships']);
                $this->logger?->info('Stored pending relationships', ['modelName' => $spec['name']]);
            }

            $model = Model::fromArray($spec);
            $this->logger?->info('Model spec processed', ['modelName' => $model->getName()]);

            //Register model to the registery
            $this->registryManager->register('model', $model);
            $this->logger?->info('Model registered in registry', ['modelName' => $model->getName()]);

            // Create the defatul query for the model
            $query = Query::fromModel($model, $this->registryManager);

            $this->logger?->info('Created default query for model', ['queryName' => $query->getName()]);

            // // register the query
            $this->registryManager->register('query', $query);
            $this->logger?->info('Default query registered in registry', [
                'modelName' => $model->getName(),
                'queryName' => $query->getName()
            ]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing model', [
                'modelName' => $spec['name'] ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function processPendingRelationships(): void
    {
        try {
            $this->logger?->info('Processing pending relationships for models');
            foreach ($this->pendingRelationships as $pending) {
                $this->logger?->info('Processing relationships for model', ['modelName' => $pending['modelName']]);
                $model = $this->registryManager->get('model', $pending['modelName']);

                if (! $model) {
                    $this->logger?->error('Model not found for relationship processing', [
                        'modelName' => $pending['modelName'],
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process relationships: Model {$pending['modelName']} not found"
                    );
                }
                // Use RelationshipProcessor to handle relationship creation
                $model->addRelationshipsFromArray($model->getName(), $pending['relationships'], $this->registryManager);

                $this->logger?->info('Successfully processed relationships for model', ['modelName' => $pending['modelName']]);
            }

            // Clear pending relationships after processing
            $this->pendingRelationships = [];
            $this->logger?->info('Successfully processed relationships for all the models');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during relationship processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing relationships', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing relationships: ' . $e->getMessage());
        }
    }

    public function processAllQuerySpec(): void
    {
        try {
            $this->logger?->info('Processing all the query specs');
            foreach ($this->pendingQueries as $pending) {

                $this->logger?->info('Processing query', ['queryName' => $pending['name']]);

                $this->processQuerySpec($pending);
            }
            // Clear pending queries after processing
            $this->pendingQueries = [];
            $this->logger?->info('Successfully processed all the queries');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during query processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing query', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing query: ' . $e->getMessage());
        }
    }

    private function processQuerySpec(array $spec): void
    {
        try {
            $this->logger?->info('Processing query spec', ['queryName' => $spec['name']]);

            $query = Query::fromArray($spec, $this->registryManager);

            $this->logger?->info('Query spec processed', ['queryName' => $query->getName()]);

            // register the query
            $this->registryManager->register('query', $query);
            $this->logger?->info('Query registered in registry', ['modelName' => $query->getName()]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing query spec', [
                'queryName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
