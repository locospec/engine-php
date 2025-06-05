<?php

namespace LCSEngine\Schemas\Model\Relationships;

abstract class Relationship
{
    protected string $foreignKey;
    protected string $relatedModelName;
    protected string $currentModelName;
    protected string $relationshipName;
    protected Type $type;

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function setForeignKey(string $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
    }

    public function setCurrentModelName(string $currentModelName): void
    {
        $this->currentModelName = $currentModelName;
    }

    public function setRelatedModelName(string $relatedModelName): void
    {
        $this->relatedModelName = $relatedModelName;
    }

    public function setRelationshipName(string $relationshipName): void
    {
        $this->relationshipName = $relationshipName;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getCurrentModelName(): string
    {
        return $this->currentModelName;
    }

    public function getRelatedModelName(): string
    {
        return $this->relatedModelName;
    }

    public function getRelationshipName(): string
    {
        return $this->relationshipName;
    }

    abstract public function toArray(): array;

    // Abstract static method for fromArray, to be implemented by concrete classes
    // The signature might need adjustment based on how fromArray will work, 
    // but this indicates the intent.
    // A factory method might be more appropriate for fromArray on the abstract class,
    // but based on the diagram it's expected on concrete classes.
    // Let's define an abstract one on the base for now.
    // abstract public static function fromArray(array $data): self;

    // Note: fromArray is typically a static factory method. Defining it as abstract
    // in the base class requires specific implementation details in derived classes.
    // It might be better implemented as a factory pattern outside the classes or
    // rely on fromArray in each concrete class.
    // Based on the diagram, I'll omit the abstract fromArray in the base class for now.
} 