<?php

namespace Locospec\EnginePhp\Models;

use InvalidArgumentException;
use Locospec\EnginePhp\Models\Relationships\BelongsTo;
use Locospec\EnginePhp\Models\Relationships\HasMany;
use Locospec\EnginePhp\Models\Relationships\HasOne;
use Locospec\EnginePhp\Schema\Schema;
use Locospec\EnginePhp\Models\Relationships\Relationship;
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
        $this->relationships[$relationship->getName()] = $relationship;
    }

    public function getRelationship(string $name): ?Relationship
    {
        return $this->relationships[$name] ?? null;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getRelationshipsByType(string $type): array
    {
        return array_filter(
            $this->relationships,
            fn(Relationship $rel) => $rel->getType() === $type
        );
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['name'])) {
            throw new InvalidArgumentException('Model name is required');
        }

        $schema = isset($data['schema']) ? Schema::fromArray($data['schema']) : new Schema();
        $config = ModelConfiguration::fromArray($data['config'] ?? []);

        $model = new self($data['name'], $schema, $config);

        if (isset($data['relationships'])) {
            $model->parseRelationships($data['relationships']);
        }

        return $model;
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
            $result[$type][$relationship->getName()] = $relationship->toArray();
        }
        return $result;
    }

    private function parseRelationships(array $relationships): void
    {
        foreach ($relationships as $type => $relations) {
            foreach ($relations as $name => $config) {
                $relationClass = $this->getRelationshipClass($type);
                if (!$relationClass) {
                    continue;
                }

                $relationship = new $relationClass(
                    $name,
                    $config['model'] ?? '',
                    $config['foreignKey'] ?? null,
                    $config['localKey'] ?? $config['ownerKey'] ?? null
                );

                if (isset($config['sortBy']) && method_exists($relationship, 'setSortBy')) {
                    $relationship->setSortBy($config['sortBy']);
                }

                $this->addRelationship($relationship);
            }
        }
    }

    private function getRelationshipClass(string $type): ?string
    {
        $map = [
            'belongs_to' => BelongsTo::class,
            'has_many' => HasMany::class,
            'has_one' => HasOne::class,
        ];

        return $map[$type] ?? null;
    }
}
