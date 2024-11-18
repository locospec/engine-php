<?php

namespace Locospec\LCS\Query;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class FilterGroup
{
    private string $operator;

    /** @var FilterCondition[] */
    private array $conditions = [];

    public function __construct(string $operator = 'and')
    {
        $this->validateOperator($operator);
        $this->operator = strtolower($operator);
    }

    private function validateOperator(string $operator): void
    {
        $validOperators = ['and', 'or'];
        if (! in_array(strtolower($operator), $validOperators)) {
            throw new InvalidArgumentException(
                'Invalid filter group operator. Valid operators are: '.implode(', ', $validOperators)
            );
        }
    }

    public function addCondition(FilterCondition $condition): self
    {
        $clone = clone $this;
        $clone->conditions[] = $condition;

        return $clone;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function validate(): void
    {
        if (empty($this->conditions)) {
            throw new InvalidArgumentException('Filter group must have at least one condition');
        }

        foreach ($this->conditions as $condition) {
            $condition->validate();
        }
    }

    public static function fromArray(array $data): self
    {
        if (! isset($data['operator'])) {
            throw new InvalidArgumentException('Filter group must specify an operator');
        }

        if (! isset($data['conditions']) || ! is_array($data['conditions'])) {
            throw new InvalidArgumentException('Filter group must specify conditions array');
        }

        $group = new self($data['operator']);

        foreach ($data['conditions'] as $conditionData) {
            $condition = FilterCondition::fromArray($conditionData);
            $group = $group->addCondition($condition);
        }

        $group->validate();

        return $group;
    }

    public function toArray(): array
    {
        return [
            'operator' => $this->operator,
            'conditions' => array_map(
                fn (FilterCondition $condition) => $condition->toArray(),
                $this->conditions
            ),
        ];
    }
}