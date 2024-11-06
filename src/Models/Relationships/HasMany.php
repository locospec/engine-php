<?php

namespace Locospec\EnginePhp\Models\Relationships;

class HasMany extends Relationship
{
    private string $foreignKey;

    private string $localKey;

    public function __construct(
        string $relationshipName,
        string $relatedModelName,
        string $foreignKey,
        string $localKey
    ) {
        parent::__construct($relationshipName, $relatedModelName);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getType(): string
    {
        return 'has_many';
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getQueryPattern(): string
    {
        return "SELECT * FROM {model} WHERE {$this->foreignKey} = :{$this->localKey}";
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'foreignKey' => $this->foreignKey,
            'localKey' => $this->localKey,
        ]);
    }
}
