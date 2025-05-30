<?php

namespace LCSEngine\Schemas\Model\Filters;

class PrimitiveFilterSet implements FilterInterface
{
    private array $filters = [];

    public function __construct()
    {
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function add(string $key, mixed $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }

    public function toArray(): array
    {
        if (empty($this->filters)) {
            return [];
        }

        $group = new FilterGroup(LogicalOperator::AND);
        foreach ($this->filters as $key => $value) {
            $group->add(new Condition($key, ComparisonOperator::IS, $value));
        }

        return $group->toArray();
    }
} 