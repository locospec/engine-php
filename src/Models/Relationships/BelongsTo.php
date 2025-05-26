<?php

namespace LCSEngine\Models\Relationships;

class BelongsTo extends Relationship
{
    private string $foreignKey;

    private string $ownerKey;

    public function __construct(
        string $relationshipName,
        string $relatedModelName,
        string $foreignKey,
        string $ownerKey
    ) {
        parent::__construct($relationshipName, $relatedModelName);
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function getType(): string
    {
        return 'belongs_to';
    }

    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getQueryPattern(): string
    {
        return "SELECT * FROM {model} WHERE {$this->ownerKey} = :{$this->foreignKey} LIMIT 1";
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'foreignKey' => $this->foreignKey,
            'ownerKey' => $this->ownerKey,
        ]);
    }
}
