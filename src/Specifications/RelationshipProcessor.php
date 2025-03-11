<?php

namespace Locospec\Engine\Specifications;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Models\Relationships\BelongsTo;
use Locospec\Engine\Models\Relationships\HasMany;
use Locospec\Engine\Models\Relationships\HasOne;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Support\StringInflector;

class RelationshipProcessor
{
    private RegistryManager $registryManager;

    private StringInflector $inflector;

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->inflector = StringInflector::getInstance();
    }

    public function processModelRelationships(ModelDefinition $currentModel, object $relationships): void
    {
        foreach ($relationships as $type => $relations) {
            if (! is_object($relations)) {
                throw new InvalidArgumentException(sprintf(
                    'Relationship type %s must be an object',
                    htmlspecialchars($type, ENT_QUOTES, 'UTF-8')
                ));
            }

            foreach ($relations as $relationshipName => $config) {
                $this->createAndValidateRelationship($currentModel, $type, $relationshipName, $config);
            }
            // clean relationships 'has_one', 'belongs_to', 'has_many'
            $currentModel->cleanRelationships();
        }
    }

    public function normalizeModelRelationships(ModelDefinition $currentModel, object $relationships): void
    {
        foreach ($relationships as $type => $relations) {
            if (! is_object($relations)) {
                throw new InvalidArgumentException(sprintf(
                    'Relationship type %s must be an object',
                    htmlspecialchars($type, ENT_QUOTES, 'UTF-8')
                ));
            }

            $normalizedRelations = new \stdClass;
            foreach ($relations as $relationshipName => $config) {
                $normalizedRelations->$relationshipName = $this->normalizeRelationship($currentModel, $type, $relationshipName, $config);
            }

            $currentModel->addNormalizedRelationship($type, $normalizedRelations);
        }
    }

    private function createAndValidateRelationship(
        ModelDefinition $currentModel,
        string $type,
        string $relationshipName,
        object $config
    ): void {
        // Get or derive the related model name
        $relatedModelName = $this->determineRelatedModelName($relationshipName, $config);
        // Validate related model exists
        $relatedModel = $this->registryManager->get('model', $relatedModelName);
        if (! $relatedModel) {
            throw new InvalidArgumentException(
                "Related model '$relatedModelName' not found for relationship '$relationshipName' in model '{$currentModel->getName()}'"
            );
        }

        // Create relationship with proper key configuration
        $relationship = match ($type) {
            'has_one' => new HasOne($relationshipName, $relatedModel->getName(), $currentModel->getRelationships()->$type->$relationshipName->foreignKey, $currentModel->getRelationships()->$type->$relationshipName->localKey),
            'belongs_to' => new BelongsTo($relationshipName, $relatedModel->getName(), $currentModel->getRelationships()->$type->$relationshipName->foreignKey, $currentModel->getRelationships()->$type->$relationshipName->ownerKey),
            'has_many' => new HasMany($relationshipName, $relatedModel->getName(), $currentModel->getRelationships()->$type->$relationshipName->foreignKey, $currentModel->getRelationships()->$type->$relationshipName->localKey),
            default => throw new InvalidArgumentException("Unsupported relationship type: $type")
        };
        // Set parent model name and description
        $relationship->setCurrentModelName($currentModel->getName());
        $relationship->setDescription("{$currentModel->getName()} $type $relationshipName");

        // Add the relationship to the model
        $currentModel->addRelationship($relationship);
    }

    private function normalizeRelationship(
        ModelDefinition $currentModel,
        string $type,
        string $relationshipName,
        object $config
    ): object {
        // Get or derive the related model name
        $relatedModelName = $this->determineRelatedModelName($relationshipName, $config);
        // Validate related model exists
        $relatedModel = $this->registryManager->get('model', $relatedModelName);
        if (! $relatedModel) {
            throw new InvalidArgumentException(
                "Related model '$relatedModelName' not found for relationship '$relationshipName' in model '{$currentModel->getName()}'"
            );
        }

        // Create relationship with proper key configuration
        $relationship = match ($type) {
            'has_one' => $this->normalizeHasOneRelationship($currentModel, $relatedModel, $relationshipName, $config),
            'belongs_to' => $this->normalizeBelongsToRelationship($currentModel, $relatedModel, $relationshipName, $config),
            'has_many' => $this->normalizeHasManyRelationship($currentModel, $relatedModel, $relationshipName, $config),
            default => throw new InvalidArgumentException("Unsupported relationship type: $type")
        };

        return $relationship;
    }

    private function normalizeHasOneRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        object $config
    ): object {
        $hasOneRelationship = new \stdClass;
        $currentModelName = $this->inflector->singular($currentModel->getName());
        $currentModelPrimaryKey = $currentModel->getConfig()->getPrimaryKey();

        // foreignKey is in related model, referencing current model's primary key
        $foreignKey = $config->foreignKey ??
            $this->inflector->snake("{$currentModelName}_{$currentModelPrimaryKey}");

        // localKey is the primary key of current model
        $localKey = $config->localKey ?? $currentModelPrimaryKey;

        $hasOneRelationship->model = $relatedModel->getName();
        $hasOneRelationship->foreignKey = $foreignKey;
        $hasOneRelationship->localKey = $localKey;

        return $hasOneRelationship;
    }

    private function normalizeBelongsToRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        object $config
    ): object {
        $belongsToRelationship = new \stdClass;

        $relatedModelName = $this->inflector->singular($relatedModel->getName());
        $relatedModelPrimaryKey = $relatedModel->getConfig()->getPrimaryKey();

        // foreignKey is in current model, referencing related model's primary key
        $foreignKey = $config->foreignKey ??
            $this->inflector->snake("{$relatedModelName}_{$relatedModelPrimaryKey}");

        // ownerKey is the primary key of related model
        $ownerKey = $config->ownerKey ?? $relatedModelPrimaryKey;

        $belongsToRelationship->model = $relatedModel->getName();
        $belongsToRelationship->foreignKey = $foreignKey;
        $belongsToRelationship->ownerKey = $ownerKey;

        return $belongsToRelationship;
    }

    private function normalizeHasManyRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        object $config
    ): object {
        $hasManyRelationship = new \stdClass;

        $currentModelName = $this->inflector->singular($currentModel->getName());
        $currentModelPrimaryKey = $currentModel->getConfig()->getPrimaryKey();

        // foreignKey is in related model, referencing current model's primary key
        $foreignKey = $config->foreignKey ??
        $this->inflector->snake("{$currentModelName}_{$currentModelPrimaryKey}");

        // localKey is the primary key of current model
        $localKey = $config->localKey ?? $currentModelPrimaryKey;
        $hasManyRelationship->model = $relatedModel->getName();
        $hasManyRelationship->foreignKey = $foreignKey;
        $hasManyRelationship->localKey = $localKey;

        return $hasManyRelationship;
    }

    private function determineRelatedModelName(string $relationshipName, object $config): string
    {
        return $config->model ?? $this->inflector->singular($relationshipName);
    }
}
