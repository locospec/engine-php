<?php

namespace Locospec\Engine\Models;

use Locospec\Engine\Models\Relationships\Relationship;
use Locospec\Engine\Models\Traits\HasAliases;
use Locospec\Engine\Schema\Schema;
use Locospec\Engine\Support\StringInflector;

class ModelDefinition
{
    use HasAliases;

    private string $name;

    private Schema $schema;

    private ModelConfiguration $config;

    private object $relationships;

    private object $scopes;

    public function __construct(string $name, Schema $schema, ModelConfiguration $config)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->config = $config;
        $this->relationships = new \stdClass;
        $this->scopes = new \stdClass;
        $this->aliases = new \stdClass;
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
        $this->relationships->{$relationship->getRelationshipName()} = $relationship;
    }

    public function addNormalizedRelationship($type, $normalizedRelations): void
    {
        $this->relationships->{$type} = $normalizedRelations;
    }

    public function getRelationship(string $relationshipName): ?Relationship
    {
        return $this->relationships->$relationshipName ?? null;
    }

    public function getRelationships(): object
    {
        return $this->relationships;
    }

    public function getRelationshipsByType(string $type): object
    {
        return array_filter(
            $this->relationships,
            fn (Relationship $rel) => $rel->getType() === $type
        );
    }

    public static function fromObject(object $data): self
    {
        // Validate the basic model structure
        ModelValidator::validate($data);

        // Create model without relationships
        $schema = isset($data->attributes) ? Schema::fromObject($data->attributes) : new Schema;

        $config = $data->config ?? new \stdClass;
        if (! isset($config->table)) {
            $config->table = StringInflector::getInstance()->plural($data->name);
        }

        $modelConfig = ModelConfiguration::fromObject($config);

        $model = new self($data->name, $schema, $modelConfig);

        if (isset($data->scopes)) {
            foreach ($data->scopes as $name => $filterSpec) {
                $model->addScope($name, $filterSpec);
            }
        }

        $model->addAliases($data);

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

    public function toObject(): object
    {
        $result = new \stdClass;
        $result->name = $this->name;
        $result->type = 'model';
        $result->config = $this->config->toObject();
        $result->attributes = $this->schema->toObject();
        $result->relationships = $this->relationships;

        if (! empty($this->scopes)) {
            $result->scopes = $this->scopes;
        }

        if (! empty($this->aliases)) {
            $result->aliases = $this->aliases;
        }

        return $result;
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

    public function addScope(string $name, object $filterSpec): void
    {
        $this->scopes->$name = $filterSpec;
    }

    public function getScope(string $name): ?array
    {
        return $this->objectToArray($this->scopes->$name) ?? null;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasScope(string $name): bool
    {
        return isset($this->scopes->$name);
    }

    private function objectToArray($obj): array
    {
        return json_decode(json_encode($obj), true);
    }
}
