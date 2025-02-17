<?php

namespace Locospec\Engine\Database\Filters;

use Locospec\Engine\Exceptions\InvalidArgumentException;

class FilterCondition
{
    private const VALID_OPERATORS = [
        'is',
        'is_not',
        'greater_than',
        'less_than',
        'greater_than_or_equal',
        'less_than_or_equal',
        'contains',
        'not_contains',
        'is_any_of',
        'is_none_of',
        'is_empty',
        'is_not_empty',
    ];

    public static function normalize(array $condition): array
    {
        // Handle nested filter group
        if (isset($condition['op']) && isset($condition['conditions'])) {
            return $condition;
        }

        if (! isset($condition['attribute'])) {
            throw new InvalidArgumentException('Filter condition must specify an attribute');
        }

        return [
            'op' => $condition['op'] ?? 'is',
            'attribute' => $condition['attribute'],
            'value' => $condition['value'] ?? null,
        ];
    }

    public static function validate(array $condition): void
    {
        // Skip validation for nested groups
        if (isset($condition['conditions'])) {
            FilterGroup::validate($condition);

            return;
        }

        if (! isset($condition['attribute'])) {
            throw new InvalidArgumentException('Filter condition must specify an attribute');
        }

        if (! isset($condition['op'])) {
            throw new InvalidArgumentException('Filter condition must specify an operator');
        }

        $operator = strtolower($condition['op']);
        if (! in_array($operator, self::VALID_OPERATORS)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}");
        }
    }
}
