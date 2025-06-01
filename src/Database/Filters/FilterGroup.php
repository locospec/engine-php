<?php

namespace LCSEngine\Database\Filters;

use LCSEngine\Exceptions\InvalidArgumentException;

class FilterGroup
{
    public static function normalize(array $operation, $model): array
    {
        if (! isset($operation['filters'])) {
            return $operation;
        }

        $filters = $operation['filters'];
        $aliases = $model->getAliases();

        // If filters is not an array, return unchanged
        if (! is_array($filters)) {
            return $operation;
        }

        // If already in full form (has op and conditions), return unchanged
        if (isset($filters['op']) && isset($filters['conditions'])) {
            foreach ($filters['conditions'] as $key => $condition) {
                if (isset($condition['op']) && isset($condition['conditions'])) {
                    foreach ($condition['conditions'] as $nestedKey => $nestedCondition) {
                        if (isset($aliases->{$nestedCondition['attribute']}) && isset($aliases->{$nestedCondition['attribute']}->source)) {
                            $filters['conditions'][$key]['conditions'][$nestedKey]['attribute'] = $aliases->{$nestedCondition['attribute']}->source;
                        }
                    }
                } else {
                    if (isset($aliases->{$condition['attribute']}) && isset($aliases->{$condition['attribute']}->source)) {
                        $filters['conditions'][$key]['attribute'] = $aliases->{$condition['attribute']}->source;
                    }
                }
            }
            $operation['filters'] = $filters;

            return $operation;
        }

        // Handle array format of conditions (numbered array)
        if (isset($filters[0])) {
            foreach ($filters as $key => $condition) {
                if (isset($aliases->{$condition['attribute']}) && isset($aliases->{$condition['attribute']}->source)) {
                    $filters[$key]['attribute'] = $aliases->{$condition['attribute']}->source;
                }
            }
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
                if (isset($aliases->{$attribute}) && isset($aliases->{$attribute}->source)) {
                    $attribute = $aliases->{$attribute}->source;
                }

                $conditions[] = [
                    'op' => 'is',
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
        if (! isset($filters['op'])) {
            throw new InvalidArgumentException('Filter group must specify an operator');
        }

        if (! in_array(strtolower($filters['op']), ['and', 'or'])) {
            throw new InvalidArgumentException("Invalid operator: {$filters['op']}");
        }

        if (! isset($filters['conditions']) || ! is_array($filters['conditions'])) {
            throw new InvalidArgumentException('Filter group must specify conditions array');
        }

        foreach ($filters['conditions'] as $condition) {
            FilterCondition::validate($condition);
        }
    }
}
