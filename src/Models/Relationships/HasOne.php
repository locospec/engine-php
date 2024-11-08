<?php

namespace Locospec\LCS\Models\Relationships;

class HasOne extends Relationship
{
    private string $foreignKey;

    private string $localKey;

    private ?array $sortBy;

    public function __construct(
        string $relationshipName,
        string $relatedModelName,
        string $foreignKey,
        string $localKey,
        ?array $sortBy = null
    ) {
        parent::__construct($relationshipName, $relatedModelName);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->sortBy = $sortBy;
    }

    public function getType(): string
    {
        return 'has_one';
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function setSortBy(?array $sortBy): void
    {
        $this->sortBy = $sortBy;
    }

    public function getSortBy(): ?array
    {
        return $this->sortBy;
    }

    public function getQueryPattern(): string
    {
        $query = "SELECT * FROM {model} WHERE {$this->foreignKey} = :{$this->localKey}";

        if ($this->sortBy) {
            $orderClauses = [];
            foreach ($this->sortBy as $column => $direction) {
                $orderClauses[] = "{$column} {$direction}";
            }
            $query .= ' ORDER BY '.implode(', ', $orderClauses);
        }

        return $query.' LIMIT 1';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'foreignKey' => $this->foreignKey,
            'localKey' => $this->localKey,
            'sortBy' => $this->sortBy,
        ]);
    }
}
