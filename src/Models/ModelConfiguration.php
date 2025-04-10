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

    private bool $softDelete;

    private string $deleteColumn;

    public function __construct(
        string $primaryKey = 'id',
        ?string $table = null,
        ?string $connection = null,
        ?string $dbOperator = null,
        ?string $singular = null,
        ?string $plural = null,
        ?string $labelKey = null,
        bool $softDelete,
        string $deleteColumn
    ) {
        $this->primaryKey = $primaryKey;
        $this->table = $table;
        $this->connection = $connection;
        $this->dbOperator = $dbOperator;
        $this->singular = $singular;
        $this->plural = $plural;
        $this->labelKey = $labelKey;
        $this->softDelete = $softDelete;
        $this->deleteColumn = $deleteColumn;
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
            $config['softDelete'] ?? true,
            $config['deleteColumn'] ?? "deleted_at",
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
            $config->softDelete ?? true,
            $config->deleteColumn ?? "deleted_at",
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

    public function getSoftDelete(): ?bool
    {
        return $this->softDelete;
    }
    
    public function getDeleteColumn(): ?string
    {
        return $this->deleteColumn;
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
            'softDelete' => $this->softDelete,
            'deleteColumn' => $this->deleteColumn,
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
        $object->softDelete = $this->softDelete;
        $object->deleteColumn = $this->deleteColumn;

        return $object;
    }
}
