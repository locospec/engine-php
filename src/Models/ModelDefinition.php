<?php

namespace Locospec\LCS\Models;

use Locospec\LCS\Models\Relationships\Relationship;
use Locospec\LCS\Models\Traits\HasAliases;
use Locospec\LCS\Schema\Schema;
use Locospec\LCS\Support\StringInflector;

class ModelDefinition
{
    use HasAliases;

    private string $name;

    private Schema $schema;

    private ModelConfiguration $config;

    private array $relationships = [];

    private array $scopes = [];

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

        $config = $data['config'] ?? [];
        if (! isset($config['table'])) {
            $config['table'] = StringInflector::getInstance()->plural($data['name']);
        }

        $modelConfig = ModelConfiguration::fromArray($config);

        $model = new self($data['name'], $schema, $modelConfig);

        if (isset($data['scopes']) && is_array($data['scopes'])) {
            foreach ($data['scopes'] as $name => $filterSpec) {
                $model->addScope($name, $filterSpec);
            }
        }

        $model->loadAliasesFromArray($data);

        return $model;
    }

    public function toArray(): array
    {
        $array = [
            'name' => $this->name,
            'type' => 'model',
            'config' => $this->config->toArray(),
            'schema' => $this->schema->toArray(),
            'relationships' => $this->relationshipsToArray(),
        ];

        if (! empty($this->scopes)) {
            $array['scopes'] = $this->scopes;
        }

        if (! empty($this->aliases)) {
            $array['aliases'] = $this->aliases;
        }

        return $array;
    }

    private function relationshipsToArray(): array
    {
        $result = [];
        foreach ($this->relationships as $relationship) {
            $type = $relationship->getType();
            if (! isset($result[$type])) {
                $result[$type] = [];
            }
            $result[$type][$relationship->getRelationshipName()] = $relationship->toArray();
        }

        return $result;
    }

    public function addScope(string $name, array $filterSpec): void
    {
        $this->scopes[$name] = $filterSpec;
    }

    public function getScope(string $name): ?array
    {
        return $this->scopes[$name] ?? null;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasScope(string $name): bool
    {
        return isset($this->scopes[$name]);
    }
}
