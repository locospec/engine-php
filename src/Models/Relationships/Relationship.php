<?php

namespace Locospec\LCS\Models\Relationships;

use Locospec\LCS\Support\StringInflector;

abstract class Relationship
{
    protected string $relationshipName;

    protected string $description;

    protected string $relatedModelName;

    protected string $currentModelName;

    public function __construct(
        string $relationshipName,
        string $relatedModelName,
        string $currentModelName = ''
    ) {
        $this->relationshipName = $relationshipName;
        $this->setRelatedModelName($relatedModelName);
        $this->currentModelName = $currentModelName;
    }

    abstract public function getType(): string;

    abstract public function getQueryPattern(): string;

    public function getRelationshipName(): string
    {
        return $this->relationshipName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRelatedModelName(): string
    {
        return $this->relatedModelName;
    }

    public function setCurrentModelName(string $modelName): void
    {
        $this->currentModelName = $modelName;
    }

    public function getCurrentModelName(): string
    {
        return $this->currentModelName;
    }

    protected function setRelatedModelName(string $modelName): void
    {
        if (empty($modelName)) {
            $inflector = StringInflector::getInstance();
            $modelName = $inflector->singular($this->relationshipName);
        }
        $this->relatedModelName = $modelName;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            'relationshipName' => $this->relationshipName,
            'relatedModelName' => $this->relatedModelName,
            'currentModelName' => $this->currentModelName,
        ];
    }
}
