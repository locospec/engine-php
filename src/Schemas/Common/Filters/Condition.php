<?php

namespace LCSEngine\Schemas\Common\Filters;

class Condition implements FilterInterface
{
    private string $attribute;

    private ComparisonOperator $op;

    private mixed $value;

    public function __construct(string $attribute, ComparisonOperator $op, mixed $value)
    {
        $this->attribute = $attribute;
        $this->op = $op;
        $this->value = $value;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getOperator(): ComparisonOperator
    {
        return $this->op;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'op' => $this->op->value,
            'value' => $this->value,
        ];
    }
}
