<?php

namespace LCSEngine\Schemas\Model;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Filters\Filters;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Relationship;
use LCSEngine\Schemas\Model\Relationships\Type as RelationshipType;
use LCSEngine\Schemas\Type;
use ValueError;

class Model
{
    protected string $name;

    protected string $label;

    protected Type $type;

    protected Collection $attributes;

    protected Collection $relationships;

    protected Collection $scopes;

    protected Configuration $config;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::MODEL;
        $this->attributes = collect();
        $this->relationships = collect();
        $this->scopes = collect();
        $this->config = new Configuration($name);
    }

    public function addAttribute(Attribute $attribute): void
    {
        $this->attributes->put($attribute->getName(), $attribute);
    }

    public function addRelationship(Relationship $relationship): void
    {
        $this->relationships->put($relationship->getRelationshipName(), $relationship);
    }

    public function getRelationship(string $name): ?Relationship
    {
        return $this->relationships->get($name);
    }

    public function addScope(string $name, Filters $filters): void
    {
        $this->scopes->put($name, $filters);
    }

    public function getScope(string $name): ?Filters
    {
        return $this->scopes->get($name);
    }

    public function removeAttribute(string $name): void
    {
        $this->attributes->forget($name);
    }

    public function removeRelationship(string $name): void
    {
        $this->relationships->forget($name);
    }

    public function removeScope(string $name): void
    {
        $this->scopes->forget($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getAttribute(string $name): Attribute
    {
        return $this->attributes->get($name);
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function getRelationships(): Collection
    {
        return $this->relationships;
    }

    public function getRelationshipsByType(string $type): Collection
    {
        return $this->relationships->filter(fn(Relationship $relationship) => $relationship->getType() === RelationshipType::from($type));
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function getPrimaryKey(): ?Attribute
    {
        return $this->attributes->first(fn(Attribute $attribute) => $attribute->isPrimaryKey());
    }

    public function getDeleteKey(): ?Attribute
    {
        return $this->attributes->first(fn(Attribute $attribute) => $attribute->isDeleteKey());
    }

    public function hasDeleteKey(): bool
    {
        return $this->getDeleteKey() !== null;
    }

    public function getLabelKey(): ?Attribute
    {
        return $this->attributes->first(fn(Attribute $attribute) => $attribute->isLabelKey());
    }

    public function getAliases(): Collection
    {
        return $this->attributes->filter(fn(Attribute $attribute) => $attribute->isAliasKey());
    }

    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';
        $label = $data['label'] ?? '';
        $model = new self($name, $label);

        if (isset($data['type']) && in_array($data['type'], array_map(fn($t) => $t->value, Type::cases()))) {
            $model->type = Type::from($data['type']);
        }

        // Attributes
        if (! empty($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $key => $attributeData) {
                $model->addAttribute(Attribute::fromArray($key, $attributeData));
            }
        }

        // // Relationships
        // if (! empty($data['relationships']) && is_array($data['relationships'])) {
        //     $model->addRelationshipsFromArray($data['name'], $data['relationships'], $registryManager);
        // }

        // Scopes (Filters)
        if (! empty($data['scopes']) && is_array($data['scopes'])) {
            foreach ($data['scopes'] as $name => $filtersData) {
                if (is_array($filtersData)) {
                    $model->addScope($name, Filters::fromArray($filtersData));
                }
            }
        }

        // Configuration
        if (! empty($data['config']) && is_array($data['config'])) {
            $model->config = Configuration::fromArray($data['name'], $data['config']);
        }

        return $model;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'attributes' => $this->attributes->map(fn(Attribute $attribute) => $attribute->toArray())->all(),
            'relationships' => $this->relationships->map(fn(Relationship $relationship) => $relationship->toArray())->all(),
            'scopes' => $this->scopes->map(fn(Filters $filters) => $filters->toArray())->all(),
            'config' => $this->config->toArray(),
        ];
    }

    public function addRelationshipsFromArray(string $modelName, array $relationshipsData, RegistryManager $registryManager): void
    {
        if (empty($relationshipsData)) {
            return;
        }

        foreach ($relationshipsData as $typeString => $relationshipsData) {
            if (! is_array($relationshipsData)) {
                // Skip or handle unexpected structure
                continue;
            }

            try {
                $type = RelationshipType::from($typeString);
            } catch (ValueError $e) {
                throw new InvalidArgumentException("Unknown relationship type: {$typeString}");
            }

            foreach ($relationshipsData as $relationshipName => $relationshipData) {
                if (! is_array($relationshipData)) {
                    // Skip or handle unexpected individual relationship data
                    continue;
                }

                // Determine the concrete class based on the type
                $concreteClass = match ($type) {
                    RelationshipType::BELONGS_TO => BelongsTo::class,
                    RelationshipType::HAS_MANY => HasMany::class,
                    RelationshipType::HAS_ONE => HasOne::class,
                    // Add other cases if you introduce new relationship types
                };
                $relationshipData['currentModelName'] = $modelName;
                $relationshipData['relationshipName'] = $relationshipName;
                $relationship = $concreteClass::fromArray($relationshipData, $registryManager);
                // Add the created relationship to the collection
                $this->addRelationship($relationship);
            }
        }
    }
}
