<?php

namespace LCSEngine\Query;

use LCSEngine\Exceptions\InvalidArgumentException;

class FilterCondition
{
    private ?string $attribute;

    private ?string $operator;

    private mixed $value;

    private ?FilterGroup $nestedConditions;

    private ?AttributePath $attributePath = null;

    private const VALID_OPERATORS = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'NOT BETWEEN',
        'IS NULL',
        'IS NOT NULL',
    ];

    private function __construct()
    {
        // Private constructor to enforce named constructors
    }

    public static function simple(string $attribute, string $operator, mixed $value): self
    {
        $condition = new self;
        $condition->attribute = $attribute;
        $condition->operator = strtoupper($operator);
        $condition->value = $value;
        $condition->nestedConditions = null;
        $condition->attributePath = AttributePath::parse($attribute);

        $condition->validate();

        return $condition;
    }

    public static function nested(FilterGroup $conditions): self
    {
        $condition = new self;
        $condition->attribute = null;
        $condition->operator = null;
        $condition->value = null;
        $condition->nestedConditions = $conditions;

        $condition->validate();

        return $condition;
    }

    public function isCompound(): bool
    {
        return $this->nestedConditions !== null;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getNestedConditions(): ?FilterGroup
    {
        return $this->nestedConditions;
    }

    public function getAttributePath(): ?AttributePath
    {
        return $this->attributePath;
    }

    public function validate(): void
    {
        if ($this->isCompound()) {
            $this->nestedConditions->validate();

            return;
        }

        if (empty($this->attribute)) {
            throw new InvalidArgumentException('Filter condition must specify an attribute');
        }

        if (empty($this->operator)) {
            throw new InvalidArgumentException('Filter condition must specify an operator');
        }

        if (! in_array($this->operator, self::VALID_OPERATORS)) {
            throw new InvalidArgumentException(
                "Invalid operator '{$this->operator}'. Valid operators are: ".
                    implode(', ', self::VALID_OPERATORS)
            );
        }

        // Validate value based on operator
        if (in_array($this->operator, ['IS NULL', 'IS NOT NULL'])) {
            if ($this->value !== null) {
                throw new InvalidArgumentException(
                    "Operator '{$this->operator}' should not have a value"
                );
            }
        } else {
            if ($this->value === null) {
                throw new InvalidArgumentException(
                    "Operator '{$this->operator}' requires a value"
                );
            }
        }
    }

    public static function fromArray(array $data): self
    {
        // Check if this is a nested condition group
        if (isset($data['operator']) && isset($data['conditions'])) {
            return self::nested(FilterGroup::fromArray($data));
        }

        // Simple condition
        if (! isset($data['attribute']) || ! isset($data['operator'])) {
            throw new InvalidArgumentException(
                "Simple filter condition must specify 'attribute' and 'operator'"
            );
        }

        return self::simple(
            $data['attribute'],
            $data['operator'],
            $data['value'] ?? null
        );
    }

    public function toArray(): array
    {
        if ($this->isCompound()) {
            return $this->nestedConditions->toArray();
        }

        return array_filter([
            'attribute' => $this->attribute,
            'operator' => $this->operator,
            'value' => $this->value,
        ], fn ($value) => $value !== null);
    }
}
