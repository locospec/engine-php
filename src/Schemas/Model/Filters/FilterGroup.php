<?php

namespace LCSEngine\Schemas\Model\Filters;

class FilterGroup implements FilterInterface
{
    private LogicalOperator $op;

    private array $conditions = [];

    public function __construct(LogicalOperator $op)
    {
        $this->op = $op;
    }

    public function getOperator(): LogicalOperator
    {
        return $this->op;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function add(FilterInterface $filter): self
    {
        $this->conditions[] = $filter;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'op' => $this->op->value,
            'conditions' => array_map(fn (FilterInterface $filter) => $filter->toArray(), $this->conditions),
        ];
    }
}
