<?php

namespace Locospec\EnginePhp\Models\Relationships;

use Locospec\EnginePhp\Support\StringInflector;

abstract class Relationship
{
    protected string $name;
    protected string $description;

    protected string $relatedModel;

    protected string $parentModel;

    public function __construct(string $name, string $relatedModel, string $parentModel = '')
    {
        $this->name = $name;
        $this->setRelatedModel($relatedModel);
        $this->parentModel = $parentModel;
    }

    abstract public function getType(): string;

    abstract public function getQueryPattern(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    public function setParentModel($parentModel): void
    {
        $this->parentModel = $parentModel;
    }

    public function getParentModel(): string
    {
        return $this->parentModel;
    }

    protected function setRelatedModel(string $model): void
    {
        $inflector = StringInflector::getInstance();

        // If model isn't provided, pluralize the relationship name
        if (empty($model)) {
            $model = $inflector->plural($this->name);
        }
        $this->relatedModel = $model;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            'name' => $this->name,
            'relatedModel' => $this->relatedModel,
            'parentModel' => $this->parentModel,
        ];
    }
}
