<?php

namespace Locospec\EnginePhp\Models\Relationships;

use Locospec\EnginePhp\Support\StringInflector;

class HasMany extends Relationship
{
    private string $foreignKey;

    private string $localKey;

    public function __construct(
        string $name,
        string $relatedModel,
        ?string $foreignKey = null,
        ?string $localKey = null
    ) {
        parent::__construct($name, $relatedModel);
        $this->setLocalKey($localKey);
        $this->setForeignKey($foreignKey);
    }

    public function getType(): string
    {
        return 'has_many';
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

    public function getQueryPattern(): string
    {
        return "SELECT * FROM {model} WHERE {$this->foreignKey} = :{$this->localKey}";
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
