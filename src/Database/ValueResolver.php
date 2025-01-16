<?php

namespace Locospec\Engine\Database;

class ValueResolver
{
    public function resolveValue(mixed $value, ?QueryContext $context): mixed
    {
        if (! is_string($value) || ! $context) {
            return $value;
        }

        if (! str_starts_with($value, '$.')) {
            return $value;
        }

        return $context->resolveValue($value);
    }

    public function resolveFilters(array $filters, ?QueryContext $context): array
    {
        if (! isset($filters['conditions'])) {
            return $filters;
        }

        $filters['conditions'] = array_map(function ($condition) use ($context) {
            if (isset($condition['conditions'])) {
                // Handle nested filter groups
                return $this->resolveFilters($condition, $context);
            }

            if (isset($condition['value'])) {
                $condition['value'] = $this->resolveValue($condition['value'], $context);
            }

            return $condition;
        }, $filters['conditions']);

        return $filters;
    }
}
