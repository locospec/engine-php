<?php

namespace LCS\Engine\Schemas\Query;

use Illuminate\Support\Collection;

class Query
{
    public string $name;

    public string $label;

    public string $model;

    public string $selectionKey;

    public Type $type;

    public SelectionType $selectionType;

    public Collection $attributes;

    public Collection $lensSimpleFilters;

    public Collection $expand;

    public Collection $allowedScopes;

    public ActionConfig $actions;

    public SerializeConfig $serialize;

    public function __construct(string $name, string $label, string $model)
    {
        $this->name = $name;
        $this->label = $label;
        $this->model = $model;
        $this->attributes = new Collection;
        $this->lensSimpleFilters = new Collection;
        $this->expand = new Collection;
        $this->allowedScopes = new Collection;
    }

    public function addAttribute(Attribute $attr): void
    {
        $this->attributes->put($attr->key, $attr);
    }

    public function removeAttribute(string $key): void
    {
        $this->attributes->forget($key);
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addLensFilter(Filter $filter): void
    {
        $this->lensSimpleFilters->put($filter->key, $filter);
    }

    public function removeLensFilter(string $key): void
    {
        $this->lensSimpleFilters->forget($key);
    }

    public function getLensFilters(): Collection
    {
        return $this->lensSimpleFilters;
    }

    public function addExpand(string $field): void
    {
        $this->expand->push($field);
    }

    public function removeExpand(string $field): void
    {
        $this->expand = $this->expand->filter(fn ($item) => $item !== $field);
    }

    public function getExpand(): Collection
    {
        return $this->expand;
    }

    public function addAllowedScope(string $scope): void
    {
        $this->allowedScopes->push($scope);
    }

    public function removeAllowedScope(string $scope): void
    {
        $this->allowedScopes = $this->allowedScopes->filter(fn ($item) => $item !== $scope);
    }

    public function getAllowedScopes(): Collection
    {
        return $this->allowedScopes;
    }

    public function setActions(ActionConfig $config): void
    {
        $this->actions = $config;
    }

    public function setSerialize(SerializeConfig $config): void
    {
        $this->serialize = $config;
    }

    public static function fromArray(array $data): self
    {
        $query = new self($data['name'], $data['label'], $data['model']);

        if (isset($data['selectionKey'])) {
            $query->selectionKey = $data['selectionKey'];
        }

        if (isset($data['type'])) {
            $query->type = Type::from($data['type']);
        }

        if (isset($data['selectionType'])) {
            $query->selectionType = SelectionType::from($data['selectionType']);
        }

        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attr) {
                $query->addAttribute(Attribute::fromArray($attr));
            }
        }

        if (isset($data['lensSimpleFilters'])) {
            foreach ($data['lensSimpleFilters'] as $filter) {
                $query->addLensFilter(Filter::fromArray($filter));
            }
        }

        if (isset($data['expand'])) {
            foreach ($data['expand'] as $field) {
                $query->addExpand($field);
            }
        }

        if (isset($data['allowedScopes'])) {
            foreach ($data['allowedScopes'] as $scope) {
                $query->addAllowedScope($scope);
            }
        }

        if (isset($data['actions'])) {
            $query->setActions(ActionConfig::fromArray($data['actions']));
        }

        if (isset($data['serialize'])) {
            $query->setSerialize(SerializeConfig::fromArray($data['serialize']));
        }

        return $query;
    }
}
