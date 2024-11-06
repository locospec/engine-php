<?php

namespace Locospec\EnginePhp\Models;

use Locospec\EnginePhp\Models\Relationships\Relationship;
use Locospec\EnginePhp\Schema\Schema;
use Locospec\EnginePhp\Support\StringInflector;

class ModelDefinition
{
    private string $name;
    private Schema $schema;
    private ModelConfiguration $config;
    private array $relationships = [];

    public function __construct(string $name, Schema $schema, ModelConfiguration $config)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSingularName(): string
    {
        return $this->config->getSingular() ??
            StringInflector::getInstance()->singular($this->name);
    }

    public function getPluralName(): string
    {
        return $this->config->getPlural() ??
            StringInflector::getInstance()->plural($this->name);
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getConfig(): ModelConfiguration
    {
        return $this->config;
    }

    public function addRelationship(Relationship $relationship): void
    {
        $this->relationships[$relationship->getRelationshipName()] = $relationship;
    }

    public function getRelationship(string $relationshipName): ?Relationship
    {
        return $this->relationships[$relationshipName] ?? null;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getRelationshipsByType(string $type): array
    {
        return array_filter(
            $this->relationships,
            fn (Relationship $rel) => $rel->getType() === $type
        );
    }

    public static function fromArray(array $data): self
    {
        // Validate the basic model structure
        ModelValidator::validate($data);

        // Create model without relationships
        $schema = isset($data['schema']) ? Schema::fromArray($data['schema']) : new Schema;
        $config = ModelConfiguration::fromArray($data['config'] ?? []);

        return new self($data['name'], $schema, $config);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => 'model',
            'config' => $this->config->toArray(),
            'schema' => $this->schema->toArray(),
            'relationships' => $this->relationshipsToArray(),
        ];
    }

    private function relationshipsToArray(): array
    {
        $result = [];
        foreach ($this->relationships as $relationship) {
            $type = $relationship->getType();
            if (!isset($result[$type])) {
                $result[$type] = [];
            }
            $result[$type][$relationship->getRelationshipName()] = $relationship->toArray();
        }

        return $result;
    }
}
