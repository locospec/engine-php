<?php

namespace Locospec\Engine\Models;

class ModelConfiguration
{
    private string $primaryKey;

    private string $table;

    private ?string $connection;

    private ?string $dbOperator;

    private ?string $singular;

    private ?string $plural;

    private ?string $labelKey;

    public function __construct(
        string $primaryKey = 'id',
        ?string $table = null,
        ?string $connection = null,
        ?string $dbOperator = null,
        ?string $singular = null,
        ?string $plural = null,
        ?string $labelKey = null
    ) {
        $this->primaryKey = $primaryKey;
        $this->table = $table;
        $this->connection = $connection;
        $this->dbOperator = $dbOperator;
        $this->singular = $singular;
        $this->plural = $plural;
        $this->labelKey = $labelKey;
    }

    public static function fromArray(array $config): self
    {
        return new self(
            $config['primaryKey'] ?? 'id',
            $config['table'] ?? null,
            $config['connection'] ?? null,
            $config['dbOperator'] ?? null,
            $config['singular'] ?? null,
            $config['plural'] ?? null,
            $config['labelKey'] ?? null,
        );
    }

    public static function fromObject(object $config): self
    {
        return new self(
            $config->primaryKey ?? 'id',
            $config->table ?? null,
            $config->connection ?? null,
            $config->dbOperator ?? null,
            $config->singular ?? null,
            $config->plural ?? null,
            $config->labelKey ?? null,
        );
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getLabelKey(): string
    {
        return $this->labelKey;
    }

    public function getTable(): ?string
    {
        return $this->table ?? $this->plural;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
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
            'connection' => $this->connection,
            'dbOperator' => $this->dbOperator,
            'singular' => $this->singular,
            'plural' => $this->plural,
            'labelKey' => $this->labelKey,
        ], fn ($value) => $value !== null);
    }

    public function toObject(): object
    {
        $object = new \stdClass;
        $object->primaryKey = $this->primaryKey;
        $object->table = $this->table;
        $object->connection = $this->connection;
        $object->dbOperator = $this->dbOperator;
        $object->singular = $this->singular;
        $object->plural = $this->plural;
        $object->labelKey = $this->labelKey;

        return $object;
    }
}
