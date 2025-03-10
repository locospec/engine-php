<?php

namespace Locospec\Engine\Models;

use Locospec\Engine\Attributes\Attributes;
use Locospec\Engine\Models\Relationships\Relationship;
use Locospec\Engine\Models\Traits\HasAliases;
use Locospec\Engine\Support\StringInflector;

class ModelDefinition
{
    use HasAliases;

    private string $type;
    
    private string $name;

    private ModelConfiguration $config;

    private object $relationships;

    private object $scopes;

    private string $label;

    private array $filterable;

    private Attributes $attributes;

    public function __construct(string $name, Attributes $attributes, ModelConfiguration $config)
    {
        $this->type = 'model';
        $this->name = $name;
        $this->attributes = $attributes;
        $this->config = $config;
        $this->relationships = new \stdClass;
        $this->scopes = new \stdClass;
        $this->aliases = new \stdClass;
        $this->label = '';
        $this->filterable = [];

    }

    public function getType(): string
    {
        return $this->type;
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

    public function getAttributes(): Attributes
    {
        return $this->attributes;
    }

    public function getFilterable(): array
    {
        return $this->filterable;
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
        $attributes = isset($data->attributes) ? Attributes::fromObject($data->attributes) : new Attributes;

        $config = $data->config ?? new \stdClass;
        if (! isset($config->table)) {
            $config->table = StringInflector::getInstance()->plural($data->name);
        }

        $modelConfig = ModelConfiguration::fromObject($config);

        $model = new self($data->name, $attributes, $modelConfig);

        if (isset($data->scopes)) {
            foreach ($data->scopes as $name => $filterSpec) {
                $model->addScope($name, $filterSpec);
            }
        }

        if (isset($data->label)) {
            $model->label = $data->label;
        }

        if (isset($data->filterable)) {
            $model->filterable = $data->filterable;
        }
        $model->addAliases($data);

        // dd($model->getConfig()->getPrimaryKey(),$model->getConfig()->getLabelKey());
        $model->addAlias('const', (object) [ 'transform' => ".".$model->getConfig()->getPrimaryKey() ]);
        $model->addAlias('title', (object) [ 'transform' => ".".$model->getConfig()->getLabelKey() ]);


        return $model;
    }

    public function toArray(): array
    {
        $array = [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
            'config' => $this->config->toArray(),
            'attributes' => $this->attributes->toArray(),
            'relationships' => $this->relationshipsToArray(),
        ];

        if (! empty($this->scopes)) {
            $array['scopes'] = $this->scopes;
        }

        if (! empty($this->aliases)) {
            $array['aliases'] = $this->aliases;
        }

        if (! empty($this->filterable)) {
            $array['filterable'] = $this->filterable;
        }

        return $array;
    }

    public function toObject(): object
    {
        $result = new \stdClass;
        $result->name = $this->name;
        $result->type = $this->type;
        $result->label = $this->label;
        $result->config = $this->config->toObject();
        $result->attributes = $this->attributes->toObject();
        $result->relationships = $this->relationships;

        if (! empty($this->scopes)) {
            $result->scopes = $this->scopes;
        }

        if (! empty($this->aliases)) {
            $result->aliases = $this->aliases;
        }

        if (! empty($this->filterable)) {
            $result->filterable = $this->filterable;
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

    public function getLabel(): string
    {
        return $this->label;
    }
   
    public function cleanRelationships(): void
    {
        unset($this->relationships->has_one);
        unset($this->relationships->belongs_to);
        unset($this->relationships->has_many);
    }
}
