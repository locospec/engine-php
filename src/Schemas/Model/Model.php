<?php

namespace LCSEngine\Schemas\Model;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\Schemas\Model\Aggregates\Aggregate;
use LCSEngine\Schemas\Model\Attributes\Attribute;
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

    protected Collection $aggregates;

    protected Configuration $config;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::MODEL;
        $this->attributes = collect();
        $this->relationships = collect();
        $this->scopes = collect();
        $this->aggregates = collect();
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

    public function addAggregate(Aggregate $aggregate): void
    {
        $this->aggregates->put($aggregate->getName(), $aggregate);
    }

    public function getAggregate(string $name): ?Aggregate
    {
        return $this->aggregates->get($name);
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
        $attribute = $this->attributes->get($name);
        if ($attribute === null) {
            throw new \RuntimeException("Attribute '{$name}' not found in model '{$this->name}'");
        }

        return $attribute;
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
        return $this->relationships->filter(fn (Relationship $relationship) => $relationship->getType() === RelationshipType::from($type));
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function getAggregates(): Collection
    {
        return $this->aggregates;
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function getTableName(): string
    {
        return $this->config->getTable();
    }

    public function getPrimaryKey(): ?Attribute
    {
        return $this->attributes->first(fn (Attribute $attribute) => $attribute->isPrimaryKey());
    }

    public function getDeleteKey(): ?Attribute
    {
        return $this->attributes->first(fn (Attribute $attribute) => $attribute->isDeleteKey());
    }

    public function hasDeleteKey(): bool
    {
        return $this->getDeleteKey() !== null;
    }

    public function getLabelKey(): ?Attribute
    {
        return $this->attributes->first(fn (Attribute $attribute) => $attribute->isLabelKey());
    }

    public function getAliases(): Collection
    {
        return $this->attributes->filter(fn (Attribute $attribute) => $attribute->isAliasKey());
    }

    public function getTransformAttributes(): Collection
    {
        return $this->attributes->filter(fn (Attribute $attribute) => $attribute->isTransformKey());
    }

    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';
        $label = $data['label'] ?? '';
        $model = new self($name, $label);

        if (isset($data['type']) && in_array($data['type'], array_map(fn ($t) => $t->value, Type::cases()))) {
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

        // Aggregates
        if (! empty($data['aggregates']) && is_array($data['aggregates'])) {
            foreach ($data['aggregates'] as $name => $aggregateData) {
                if (is_array($aggregateData)) {
                    $model->addAggregate(Aggregate::fromArray($name, $aggregateData));
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
            'attributes' => $this->attributes->map(fn (Attribute $attribute) => $attribute->toArray())->all(),
            'relationships' => $this->relationships->map(fn (Relationship $relationship) => $relationship->toArray())->all(),
            'scopes' => $this->scopes->map(fn (Filters $filters) => $filters->toArray())->all(),
            'aggregates' => $this->aggregates->map(fn (Aggregate $aggregate) => $aggregate->toArray())->all(),
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

    /**
     * Get attributes that are not alias keys or transform keys
     *
     * @return Collection Collection of attributes that are not alias keys or transform keys
     */
    public function getAttributesOnly(): Collection
    {
        return $this->attributes->filter(fn (Attribute $attribute) => ! $attribute->isAliasKey());
    }

    /**
     * Get JOIN structures needed to reach a target via relationship path.
     * Simple core functionality - just returns the pre-calculated JOINs.
     *
     * @param  string  $relationshipPath  e.g., "user", "user.profile", "city.state"
     */
    public function getJoinsTo(string $relationshipPath, \LCSEngine\Registry\RegistryManager $registryManager): ?array
    {
        $modelRegistry = $registryManager->getRegistry('model');

        return $modelRegistry?->getPathJoins($this->name, $relationshipPath);
    }

    /**
     * Get the fully qualified attribute name with comprehensive information.
     *
     * @param  string  $attributeName  The attribute name to qualify
     * @param  RegistryManager  $registryManager  Registry manager to resolve models
     * @return array Array containing:
     *   - 'original' => original attribute name
     *   - 'qualified' => fully qualified attribute name
     *   - 'isAlias' => whether this is an alias attribute
     *   - 'isRelationship' => whether this involves a relationship
     *   - 'relationshipPath' => the relationship path (if isRelationship is true)
     *   - 'finalAttribute' => the final attribute name (after relationship resolution)
     * @throws \InvalidArgumentException If attribute doesn't exist in model
     */
    public function getQualifiedAttributeName(string $attributeName, RegistryManager $registryManager): array
    {
        $currentTableName = $this->getTableName();
        $result = [
            'original' => $attributeName,
            'qualified' => null,
            'isAlias' => false,
            'isRelationship' => false,
            'isSqlExpression' => false,
            'relationshipPath' => null,
            'finalAttribute' => $attributeName,
        ];
        
        // Step 1: Check if attribute exists and resolve the source
        $sourceToResolve = $attributeName;
        
        if (!str_contains($attributeName, '.')) {
            // Simple attribute name - must exist in model
            $attribute = $this->attributes->get($attributeName);
            if (!$attribute) {
                throw new \InvalidArgumentException("Attribute '{$attributeName}' does not exist in model '{$this->name}'");
            }
            
            // Check if it's an alias and get the source
            $result['isAlias'] = $attribute->isAliasKey();
            if ($attribute->isAliasKey() && $attribute->hasAliasSource()) {
                $sourceToResolve = $attribute->getAliasSource();
            }
        }
        
        // Step 2: Check if the source is a SQL expression
        if ($this->isSqlExpression($sourceToResolve)) {
            // This is a SQL expression - return as-is without qualification
            $result['qualified'] = $sourceToResolve;
            $result['finalAttribute'] = $sourceToResolve;
            $result['isSqlExpression'] = true;
            return $result;
        }
        
        // Step 3: Handle dots (qualified names or relationship paths)
        if (str_contains($sourceToResolve, '.')) {
            $parts = explode('.', $sourceToResolve, 2);
            
            // If first part is current table name, it's already qualified
            if ($parts[0] === $currentTableName) {
                $result['qualified'] = $sourceToResolve;
                $result['finalAttribute'] = $parts[1];
                return $result;
            }
            
            // Try to resolve as relationship path
            $allParts = explode('.', $sourceToResolve);
            $finalAttribute = array_pop($allParts);
            
            if (!empty($allParts)) {
                $relationshipPath = implode('.', $allParts);
                
                // Get joins for relationship path
                $joins = $this->getJoinsTo($relationshipPath, $registryManager);
                if ($joins && !empty($joins)) {
                    // Valid relationship - use target table from last join
                    $targetTableName = $joins[count($joins) - 1]['table'];
                    $result['qualified'] = $targetTableName . '.' . $finalAttribute;
                    $result['isRelationship'] = true;
                    $result['relationshipPath'] = $relationshipPath;
                    $result['finalAttribute'] = $finalAttribute;
                    return $result;
                }
            }
            
            // If not a valid relationship, could be a qualified name from another table
            // or invalid - just return as-is
            $result['qualified'] = $sourceToResolve;
            $result['finalAttribute'] = $parts[1] ?? $sourceToResolve;
            return $result;
        }
        
        // Step 4: Simple source - just prefix with current table name
        $result['qualified'] = $currentTableName . '.' . $sourceToResolve;
        $result['finalAttribute'] = $sourceToResolve;
        return $result;
    }

    /**
     * Determines if a source is a SQL expression.
     * Returns true for SQL expressions like functions, CASE statements, etc.
     *
     * @param  string  $source  The source to check
     * @return bool True if it's a SQL expression, false otherwise
     */
    private function isSqlExpression(string $source): bool
    {
        // Check if this is a CASE expression
        if (preg_match('/^CASE\s+/i', $source)) {
            return true;
        }

        // Check if this is an aggregate function (COUNT, SUM, AVG, MIN, MAX)
        if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $source)) {
            return true;
        }

        // Check for common SQL functions (including nested ones)
        if (preg_match('/^(CAST|COALESCE|CONCAT|NULLIF|IFNULL|IF|TRIM|UPPER|LOWER|SUBSTRING|LEFT|RIGHT|REPLACE)\s*\(/i', $source)) {
            return true;
        }

        // Check for any function pattern (letters/underscores followed by opening parenthesis)
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\s*\(/i', $source)) {
            return true;
        }

        // If none of the above patterns match, it's likely a simple column name
        return false;
    }
}
