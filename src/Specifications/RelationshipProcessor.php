<?php

namespace Locospec\EnginePhp\Specifications;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Models\ModelDefinition;
use Locospec\EnginePhp\Models\Relationships\BelongsTo;
use Locospec\EnginePhp\Models\Relationships\HasMany;
use Locospec\EnginePhp\Models\Relationships\HasOne;
use Locospec\EnginePhp\Registry\RegistryManager;
use Locospec\EnginePhp\Support\StringInflector;

class RelationshipProcessor
{
    private RegistryManager $registryManager;

    private StringInflector $inflector;

    public function __construct(RegistryManager $registryManager)
    {
        $this->registryManager = $registryManager;
        $this->inflector = StringInflector::getInstance();
    }

    public function processModelRelationships(ModelDefinition $currentModel, array $relationships): void
    {
        foreach ($relationships as $type => $relations) {
            if (! is_array($relations)) {
                throw new InvalidArgumentException("Relationship type '$type' must be an array");
            }

            foreach ($relations as $relationshipName => $config) {
                $this->createAndValidateRelationship($currentModel, $type, $relationshipName, $config);
            }
        }
    }

    private function createAndValidateRelationship(
        ModelDefinition $currentModel,
        string $type,
        string $relationshipName,
        array $config
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
            'has_one' => $this->createHasOneRelationship($currentModel, $relatedModel, $relationshipName, $config),
            'belongs_to' => $this->createBelongsToRelationship($currentModel, $relatedModel, $relationshipName, $config),
            'has_many' => $this->createHasManyRelationship($currentModel, $relatedModel, $relationshipName, $config),
            default => throw new InvalidArgumentException("Unsupported relationship type: $type")
        };

        // Set parent model name and description
        $relationship->setCurrentModelName($currentModel->getName());
        $relationship->setDescription("{$currentModel->getName()} $type $relationshipName");

        // Add the relationship to the model
        $currentModel->addRelationship($relationship);
    }

    private function createHasOneRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        array $config
    ): HasOne {
        $currentModelName = $this->inflector->singular($currentModel->getName());
        $currentModelPrimaryKey = $currentModel->getConfig()->getPrimaryKey();

        // foreignKey is in related model, referencing current model's primary key
        $foreignKey = $config['foreignKey'] ??
            $this->inflector->snake("{$currentModelName}_{$currentModelPrimaryKey}");

        // localKey is the primary key of current model
        $localKey = $config['localKey'] ?? $currentModelPrimaryKey;

        return new HasOne($relationshipName, $relatedModel->getName(), $foreignKey, $localKey);
    }

    private function createBelongsToRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        array $config
    ): BelongsTo {
        $relatedModelName = $this->inflector->singular($relatedModel->getName());
        $relatedModelPrimaryKey = $relatedModel->getConfig()->getPrimaryKey();

        // foreignKey is in current model, referencing related model's primary key
        $foreignKey = $config['foreignKey'] ??
            $this->inflector->snake("{$relatedModelName}_{$relatedModelPrimaryKey}");

        // ownerKey is the primary key of related model
        $ownerKey = $config['ownerKey'] ?? $relatedModelPrimaryKey;

        return new BelongsTo($relationshipName, $relatedModel->getName(), $foreignKey, $ownerKey);
    }

    private function createHasManyRelationship(
        ModelDefinition $currentModel,
        ModelDefinition $relatedModel,
        string $relationshipName,
        array $config
    ): HasMany {
        $currentModelName = $this->inflector->singular($currentModel->getName());
        $currentModelPrimaryKey = $currentModel->getConfig()->getPrimaryKey();

        // foreignKey is in related model, referencing current model's primary key
        $foreignKey = $config['foreignKey'] ??
            $this->inflector->snake("{$currentModelName}_{$currentModelPrimaryKey}");

        // localKey is the primary key of current model
        $localKey = $config['localKey'] ?? $currentModelPrimaryKey;

        return new HasMany($relationshipName, $relatedModel->getName(), $foreignKey, $localKey);
    }

    private function determineRelatedModelName(string $relationshipName, array $config): string
    {
        return $config['model'] ?? $this->inflector->singular($relationshipName);
    }
}
