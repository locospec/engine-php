<?php

namespace LCSEngine\Schemas\Model;

use LCSEngine\Support\StringInflector;

class Configuration
{
    protected string $connection = 'default';

    protected string $table;

    protected string $singular;

    protected string $plural;

    protected bool $softDelete = true;

    public function __construct(string $modelName)
    {
        $this->singular = StringInflector::getInstance()->singular($modelName);
        $this->plural = StringInflector::getInstance()->plural($modelName);
        $this->table = $this->plural;
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function setSingular(string $modelName): void
    {
        // $this->singular = StringInflector::getInstance()->singularize($modelName);
        $this->singular = StringInflector::getInstance()->singular($modelName);
    }

    public function setPlural(string $modelName): void
    {
        // $this->plural = StringInflector::getInstance()->pluralize($modelName);
        $this->plural = StringInflector::getInstance()->plural($modelName);

        // If table is not explicitly set, use plural
        if (empty($this->table)) {
            $this->table = $this->plural;
        }
    }

    public function setSoftDelete(bool $flag): void
    {
        $this->softDelete = $flag;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getSingular(): string
    {
        return $this->singular;
    }

    public function getPlural(): string
    {
        return $this->plural;
    }

    public function getSoftDelete(): bool
    {
        return $this->softDelete;
    }

    public function toArray(): array
    {
        return [
            'connection' => $this->connection,
            'table' => $this->table,
            'singular' => $this->singular,
            'plural' => $this->plural,
            'softDelete' => $this->softDelete,
        ];
    }

    public static function fromArray(string $modelName, array $data): self
    {
        $config = new self($modelName);

        if (isset($data['connection'])) {
            $config->connection = $data['connection'];
        }
        if (isset($data['singular'])) {
            $config->singular = $data['singular'];
        }
        if (isset($data['plural'])) {
            $config->plural = $data['plural'];
        }
        if (isset($data['table'])) {
            $config->table = $data['table'];
        } elseif (! empty($config->plural)) {
            $config->table = $config->plural;
        }
        if (isset($data['softDelete'])) {
            $config->softDelete = (bool) $data['softDelete'];
        }

        return $config;
    }
}
