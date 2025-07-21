<?php

namespace LCSEngine\Schemas\DatabaseOperations;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Common\Filters\Filters;

class Select
{
    private string $type = 'select';

    private ?string $purpose;

    private string $modelName;

    private ?string $deleteColumn;

    private Collection $scopes;

    private Filters $filters;

    private Collection $sorts;

    private Collection $attributes;

    private ?Pagination $pagination;

    private Collection $expand;

    private Collection $joins;

    public function __construct(
        string $modelName,
        ?string $purpose = null,
        ?string $deleteColumn = null
    ) {
        $this->modelName = $modelName;
        $this->purpose = $purpose;
        $this->deleteColumn = $deleteColumn;
        $this->scopes = new Collection;
        $this->filters = Filters::fromArray([]);
        $this->sorts = new Collection;
        $this->attributes = new Collection;
        $this->pagination = null;
        $this->expand = new Collection;
        $this->joins = new Collection;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getDeleteColumn(): ?string
    {
        return $this->deleteColumn;
    }

    public function addScope(Scope $scope): void
    {
        if (! $this->scopes->has($scope->getName())) {
            $this->scopes->put($scope->getName(), $scope);
        }
    }

    public function removeScope(string $scopeName): void
    {
        $this->scopes->forget($scopeName);
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function setFilters(Filters $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): Filters
    {
        return $this->filters;
    }

    public function addSort(Sort $sort): void
    {
        $this->sorts->push($sort);
    }

    public function setSorts(array $sorts): void
    {
        $this->sorts = new Collection(array_map(
            fn (array $sort): Sort => Sort::fromArray($sort),
            $sorts
        ));
    }

    public function getSorts(): Collection
    {
        return $this->sorts;
    }

    public function addAttribute(string $attribute): void
    {
        if (! $this->attributes->contains($attribute)) {
            $this->attributes->push($attribute);
        }
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = new Collection($attributes);
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function setPagination(?array $pagination): void
    {
        $this->pagination = $pagination ? Pagination::fromArray($pagination) : null;
    }

    public function getPagination(): ?array
    {
        return $this->pagination?->toArray();
    }

    public function addExpand(string $expand): void
    {
        $expandObj = new Expand($expand);
        if (! $this->expand->contains(fn (Expand $e) => $e->getPath() === $expand)) {
            $this->expand->push($expandObj);
        }
    }

    public function removeExpand(string $expand): void
    {
        $this->expand = $this->expand->filter(fn (Expand $item) => $item->getPath() !== $expand);
    }

    public function getExpand(): Collection
    {
        return $this->expand;
    }

    public function addJoin(Join $join): void
    {
        $this->joins->push($join);
    }

    public function setJoins(array $joins): void
    {
        $this->joins = new Collection(array_map(
            fn (array $join): Join => Join::fromArray($join),
            $joins
        ));
    }

    public function getJoins(): Collection
    {
        return $this->joins;
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'modelName' => $this->modelName,
        ];

        if ($this->purpose !== null) {
            $data['purpose'] = $this->purpose;
        }

        if ($this->deleteColumn !== null) {
            $data['deleteColumn'] = $this->deleteColumn;
        }

        if ($this->scopes->isNotEmpty()) {
            $data['scopes'] = $this->scopes->map(fn (Scope $scope): array => $scope->toArray())->all();
        }

        $filters = $this->filters->toArray();
        if (! empty($filters)) {
            $data['filters'] = $filters;
        }

        if ($this->sorts->isNotEmpty()) {
            $data['sorts'] = $this->sorts->map(fn (Sort $sort): array => $sort->toArray())->all();
        }

        if ($this->attributes->isNotEmpty()) {
            $data['attributes'] = $this->attributes->values()->all();
        }

        if ($this->pagination !== null) {
            $data['pagination'] = $this->pagination->toArray();
        }

        if ($this->expand->isNotEmpty()) {
            $data['expand'] = $this->expand->map(fn (Expand $expand) => $expand->getPath())->values()->all();
        }

        if ($this->joins->isNotEmpty()) {
            $data['joins'] = $this->joins->map(fn (Join $join): array => $join->toArray())->all();
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $select = new self(
            $data['modelName'],
            $data['purpose'] ?? null,
            $data['deleteColumn'] ?? null
        );

        if (isset($data['scopes'])) {
            foreach ($data['scopes'] as $scopeData) {
                $select->addScope(Scope::fromArray($scopeData));
            }
        }

        if (isset($data['filters'])) {
            $select->setFilters(Filters::fromArray($data['filters']));
        }

        if (isset($data['sorts'])) {
            foreach ($data['sorts'] as $sortData) {
                $select->addSort(Sort::fromArray($sortData));
            }
        }

        if (isset($data['attributes'])) {
            $select->setAttributes($data['attributes']);
        }

        if (isset($data['pagination'])) {
            $select->setPagination($data['pagination']);
        }

        if (isset($data['expand'])) {
            foreach ($data['expand'] as $expandPath) {
                $select->addExpand($expandPath);
            }
        }

        if (isset($data['joins'])) {
            foreach ($data['joins'] as $joinData) {
                $select->addJoin(Join::fromArray($joinData));
            }
        }

        return $select;
    }
}
