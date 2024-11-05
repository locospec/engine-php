<?php

namespace Locospec\EnginePhp\Models\Relationships;

use Locospec\EnginePhp\Support\StringInflector;

class HasOne extends Relationship
{
    private string $foreignKey;

    private string $localKey;

    private ?array $sortBy;

    public function __construct(
        string $name,
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $localKey = null,
        ?array $sortBy = null
    ) {
        parent::__construct($name, $relatedModel);
        $this->setLocalKey($localKey);
        $this->setForeignKey($foreignKey);
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

    public function setLocalKey(?string $localKey = null): void
    {
        $this->localKey = $localKey ?? 'id';
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function setForeignKey(?string $foreignKey = null): void
    {
        if ($foreignKey) {
            $this->foreignKey = $foreignKey;

            return;
        }

        $inflector = StringInflector::getInstance();
        $modelName = $inflector->singular($this->parentModel);
        $this->foreignKey = $inflector->snake($modelName) . '_id';
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
            $query .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        return $query . ' LIMIT 1';
    }

    public function getKeys(): array
    {
        return [
            'foreignKey' => $this->foreignKey,
            'localKey' => $this->localKey,
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), $this->getKeys());
    }
}
