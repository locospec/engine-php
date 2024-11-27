<?php

namespace Locospec\LCS\Database\Filters;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class FilterGroup
{
    public static function normalize(array $operation): array
    {
        if (!isset($operation['filters'])) {
            return $operation;
        }

        $filters = $operation['filters'];

        // If filters is not an array, return unchanged
        if (!is_array($filters)) {
            return $operation;
        }

        // If already in full form (has op and conditions), return unchanged
        if (isset($filters['op']) && isset($filters['conditions'])) {
            return $operation;
        }

        // Handle array format of conditions (numbered array)
        if (isset($filters[0])) {
            $operation['filters'] = [
                'op' => 'and',
                'conditions' => array_map(
                    [FilterCondition::class, 'normalize'],
                    $filters
                ),
            ];
            return $operation;
        }

        // Handle key-value shorthand format
        if (is_array($filters)) {
            $conditions = [];
            foreach ($filters as $attribute => $value) {
                $conditions[] = [
                    'op' => 'eq',
                    'attribute' => $attribute,
                    'value' => $value,
                ];
            }

            $operation['filters'] = [
                'op' => 'and',
                'conditions' => $conditions,
            ];
        }

        return $operation;
    }

    public static function validate(array $filters): void
    {
        if (!isset($filters['op'])) {
            throw new InvalidArgumentException('Filter group must specify an operator');
        }

        if (!in_array(strtolower($filters['op']), ['and', 'or'])) {
            throw new InvalidArgumentException("Invalid operator: {$filters['op']}");
        }

        if (!isset($filters['conditions']) || !is_array($filters['conditions'])) {
            throw new InvalidArgumentException('Filter group must specify conditions array');
        }

        foreach ($filters['conditions'] as $condition) {
            FilterCondition::validate($condition);
        }
    }
}
