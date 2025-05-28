<?php

namespace LCSEngine\Query;

class Query
{
    private string $modelName;

    private ?FilterGroup $filters = null;

    private ?SortCollection $sorts = null;

    private ?Pagination $pagination = null;

    public function __construct(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function setFilters(FilterGroup $filters): self
    {
        $clone = clone $this;
        $clone->filters = $filters;

        return $clone;
    }

    public function setSorts(SortCollection $sorts): self
    {
        $clone = clone $this;
        $clone->sorts = $sorts;

        return $clone;
    }

    public function setPagination(Pagination $pagination): self
    {
        $clone = clone $this;
        $clone->pagination = $pagination;

        return $clone;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getFilters(): ?FilterGroup
    {
        return $this->filters;
    }

    public function getSorts(): ?SortCollection
    {
        return $this->sorts;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }

    public static function fromArray(array $data, string $modelName): self
    {
        $query = new self($modelName);

        if (isset($data['filters']) && ! empty($data['filters'])) {
            $query = $query->setFilters(FilterGroup::fromArray($data['filters']));
        }

        if (isset($data['sorts']) && ! empty($data['sorts'])) {
            $query = $query->setSorts(SortCollection::fromArray($data['sorts']));
        }

        if (isset($data['pagination']) && ! empty($data['pagination'])) {
            $query = $query->setPagination(
                isset($data['pagination']['cursor'])
                    ? CursorPagination::fromArray($data['pagination'])
                    : OffsetPagination::fromArray($data['pagination'])
            );
        }

        return $query;
    }

    public function toArray(): array
    {
        $data = [
            'model' => $this->modelName,
        ];

        if ($this->filters !== null) {
            $data['filters'] = $this->filters->toArray();
        }

        if ($this->sorts !== null) {
            $data['sorts'] = $this->sorts->toArray();
        }

        if ($this->pagination !== null) {
            $data['pagination'] = $this->pagination->toArray();
        }

        return $data;
    }
}
