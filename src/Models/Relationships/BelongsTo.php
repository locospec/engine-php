<?php

namespace Locospec\EnginePhp\Models\Relationships;

use Locospec\EnginePhp\Support\StringInflector;

class BelongsTo extends Relationship
{
    private string $foreignKey;

    private string $ownerKey;

    public function __construct(
        string $name,
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $ownerKey = null
    ) {
        parent::__construct($name, $relatedModel);
        $this->setOwnerKey($ownerKey);
        $this->setForeignKey($foreignKey);
    }

    public function getType(): string
    {
        return 'belongs_to';
    }

    public function getOwnerKey(): string
    {
        if (is_null($this->ownerKey)) {
            return null;
        }

        return $this->ownerKey;
    }

    public function setOwnerKey(?string $ownerKey = null): void
    {
        if (!is_null($ownerKey)) {
            $this->ownerKey = $ownerKey;
        }
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
        $modelName = $inflector->singular($this->getRelatedModel());
        $this->foreignKey = $inflector->snake($modelName) . '_id';
    }

    public function getQueryPattern(): string
    {
        return "SELECT * FROM {model} WHERE {$this->ownerKey} = :{$this->foreignKey} LIMIT 1";
    }

    public function getKeys(): array
    {
        return [
            'foreignKey' => $this->getForeignKey(),
            'ownerKey' => $this->getOwnerKey(),
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), $this->getKeys());
    }
}
