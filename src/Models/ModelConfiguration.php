<?php

namespace Locospec\EnginePhp\Models;

class ModelConfiguration
{
    private string $primaryKey;

    private ?string $table;

    private ?string $dbOperator;

    private ?string $singular;

    private ?string $plural;

    public function __construct(
        string $primaryKey = 'id',
        ?string $table = null,
        ?string $dbOperator = null,
        ?string $singular = null,
        ?string $plural = null
    ) {
        $this->primaryKey = $primaryKey;
        $this->table = $table;
        $this->dbOperator = $dbOperator;
        $this->singular = $singular;
        $this->plural = $plural;
    }

    public static function fromArray(array $config): self
    {
        return new self(
            $config['primaryKey'] ?? 'id',
            $config['table'] ?? null,
            $config['dbOperator'] ?? null,
            $config['singular'] ?? null,
            $config['plural'] ?? null
        );
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getDbOperator(): ?string
    {
        return $this->dbOperator;
    }

    public function getSingular(): ?string
    {
        return $this->singular;
    }

    public function getPlural(): ?string
    {
        return $this->plural;
    }

    public function toArray(): array
    {
        return array_filter([
            'primaryKey' => $this->primaryKey,
            'table' => $this->table,
            'dbOperator' => $this->dbOperator,
            'singular' => $this->singular,
            'plural' => $this->plural,
        ], fn ($value) => $value !== null);
    }
}