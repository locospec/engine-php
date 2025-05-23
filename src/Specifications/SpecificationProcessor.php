<?php

namespace Locospec\Engine\Specifications;

use Locospec\Engine\Entities\EntityDefinition;
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\LCS;
use Locospec\Engine\Logger;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Mutators\MutatorDefinition;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\SpecValidator;
use Locospec\Engine\Views\ViewDefinition;

class SpecificationProcessor
{
    private RegistryManager $registryManager;

    private array $pendingRelationships = [];

    private array $pendingViews = [];

    private ?Logger $logger = null;

    private array $pendingMutators = [];

    private array $pendingEntities = [];

    private SpecValidator $specValidator;

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
                switch ($spec->type) {
                    case 'model':
                        $this->processModelSpec($spec);
                        break;

                    case 'view':
                        $this->pendingViews[] = $spec;
                        break;

                    case 'mutator':
                        $this->pendingMutators[] = $spec;
                        break;

                    case 'entity':
                        $this->pendingEntities[] = $spec;
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
            $this->validateSpec($spec);

            // Convert object to ModelDefinition
            $model = ModelDefinition::fromObject($spec);

            $this->logger?->info('Normalized model spec', ['modelName' => $model->getName()]);

            // Revalidate after conversion
            $this->validateSpec($model->toObject());

            $this->registryManager->register($spec->type, $model);
            $this->logger?->info('Model registered in registry', ['modelName' => $model->getName()]);

            if (isset($spec->defaultView)) {
                if (isset($spec->defaultView->model)) {
                    $this->pendingViews[] = $spec->defaultView;
                } else {
                    $spec->defaultView->model = $model->getName();
                    $this->pendingViews[] = $spec->defaultView;
                }
            } else {
                // Create the defatul view
                $view = ViewDefinition::fromModel($model, $spec, $this->registryManager);

                $this->logger?->info('Normalized default view for model', ['viewName' => $view->getName()]);

                // register the view
                $this->registryManager->register('view', $view);
                $this->logger?->info('View registered in registry', ['modelName' => $view->getName()]);
            }
        } catch (\Exception $e) {
            $this->logger?->error('Error processing model spec', [
                'modelName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function validateSpec(object $spec): void
    {
        $this->logger?->info('Validating '.$spec->type.' spec', [$spec->type.'Name' => $spec->name]);
        $validation = $this->specValidator->validateSpec($spec);

        // throw exceptions when validation fails
        if (! $validation['isValid']) {
            foreach ($validation['errors'] as $path => $errors) {
                $errorMessages[] = "$path: ".implode(', ', $errors);
            }

            $this->logger?->error($spec->type.' validation failed', [
                $spec->type.'Name' => $spec->name,
                'errors' => $errorMessages,
            ]);

            throw new InvalidArgumentException(
                $spec->type.' validation failed: '.implode(', ', $errorMessages)
            );
        }
        $this->logger?->info($spec->type.' spec validated successfully', [$spec->type.'Name' => $spec->name]);
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
                        'modelName' => $pending->modelName,
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process relationships: Model {$pending->modelName} not found"
                    );
                }

                // Use RelationshipProcessor to handle relationship creation
                $relationshipProcessor = new RelationshipProcessor($this->registryManager);

                // Normalize and validate relationships
                $relationshipProcessor->normalizeModelRelationships($model, $pending->relationships);
                $this->validateSpec($model->toObject());

                // Register relationships
                $relationshipProcessor->processModelRelationships($model, $pending->relationships);

                $this->logger?->info('Successfully processed relationships for model', ['modelName' => $pending->modelName]);
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
            throw new InvalidArgumentException('Error processing relationships: '.$e->getMessage());
        }
    }

    /**
     * Process a all view definition
     */
    public function processAllViewSpec(): void
    {
        try {
            $this->logger?->info('Processing all the views');
            foreach ($this->pendingViews as $pending) {
                $this->logger?->info('Processing view', ['viewName' => $pending->name]);
                $model = $this->registryManager->get('model', $pending->model);

                if (! $model) {
                    $this->logger?->error('Model not found for view processing', [
                        'modelName' => $pending->model,
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process view: Model {$pending->model} not found"
                    );
                }

                $this->processViewSpec($pending, $model);
            }
            // Clear pending views after processing
            $this->pendingViews = [];
            $this->logger?->info('Successfully processed all the views');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during view processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing view', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing view: '.$e->getMessage());
        }
    }

    /**
     * Process a single view definition
     */
    private function processViewSpec(object $spec, ModelDefinition $model): void
    {
        try {
            $this->logger?->info('Processing view spec', ['viewName' => $spec->name]);

            // Validate the model spec
            $this->validateSpec($spec);

            // Convert object to ViewDefinition
            $view = ViewDefinition::fromObject($spec, $this->registryManager, $model);

            $this->logger?->info('Normalized view spec', ['viewName' => $view->getName()]);

            // register the view
            $this->registryManager->register('view', $view);
            $this->logger?->info('View registered in registry', ['modelName' => $view->getName()]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing view spec', [
                'viewName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
            throw new InvalidArgumentException('Error processing mutator: '.$e->getMessage());
        }
    }

    /**
     * Process a single mutator definition
     */
    private function processMutatorSpec(object $spec, ModelDefinition $model): void
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

    /**
     * Process a all entities definition
     */
    public function processAllEntitySpec(): void
    {
        try {
            $this->logger?->info('Processing all the entities');
            foreach ($this->pendingEntities as $pending) {
                $this->logger?->info('Processing entity', ['entityName' => $pending->name]);
                $model = $this->registryManager->get('model', $pending->model);

                if (! $model) {
                    $this->logger?->error('Model not found for entity processing', [
                        'modelName' => $pending->model,
                    ]);
                    throw new InvalidArgumentException(
                        "Cannot process entity: Model {$pending->model} not found"
                    );
                }

                $this->processEntitySpec($pending, $model);
            }
            // Clear pending mutators after processing
            $this->pendingEntities = [];
            $this->logger?->info('Successfully processed all the entities');
        } catch (InvalidArgumentException $e) {
            $this->logger?->error('InvalidArgumentException during entity processing', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to be caught in LCS.php
        } catch (\Exception $e) {
            $this->logger?->error('Unexpected error processing entity', [
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Error processing entity: '.$e->getMessage());
        }
    }

    /**
     * Process a single entity definition
     */
    private function processEntitySpec(object $spec, ModelDefinition $model): void
    {
        try {
            $this->logger?->info('Processing entity spec', ['entitySpecName' => $spec->name]);

            // Validate the entity spec
            $this->validateSpec($spec);
            // Convert object to EntityDefinition
            $entitySpec = EntityDefinition::fromObject($spec, $this->registryManager, $model);

            $this->logger?->info('Normalized entity spec', ['entitySpecName' => $entitySpec->getName()]);

            // register the entity
            $this->registryManager->register('entity', $entitySpec);

            $this->logger?->info('Entity registered in registry', ['modelName' => $entitySpec->getName()]);
        } catch (\Exception $e) {
            $this->logger?->error('Error processing entity spec', [
                'entityName' => $spec->name ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
