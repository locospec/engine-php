<?php

namespace LCSEngine\Database;

use LCSEngine\Schemas\Model\Model;

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

    /**
     * Recursively resolve any '$.' placeholders in the data rows.
     *
     * @param  array  $data  Array of rows (each row is an associative array).
     * @param  QueryContext|null  $context  QueryContext for placeholder resolution.
     * @return array The data with all placeholders resolved.
     */
    public function resolveData(array $data, ?QueryContext $context): array
    {
        return array_map(fn ($row) => $this->resolveRow($row, $context), $data);
    }

    /**
     * Resolve one row (recursively handling nested arrays).
     *
     * @param  mixed  $value  A scalar or array value from the row.
     * @param  QueryContext|null  $context  QueryContext for placeholder resolution.
     * @return mixed The resolved value.
     */
    protected function resolveRow(mixed $value, ?QueryContext $context): mixed
    {
        if (is_array($value)) {
            // Recursively resolve each element in the array
            return array_map(fn ($item) => $this->resolveRow($item, $context), $value);
        }

        // Otherwise it's a scalar—use resolveValue to check for '$.' placeholders
        return $this->resolveValue($value, $context);
    }

    /**
     * JSON-encode any columns defined as 'json' or 'object' in the model spec.
     *
     * @param  array  $data  Array of rows to process.
     * @param  Model|null  $model  Model holding attribute types.
     * @return array The data with JSON columns encoded.
     */
    public function resolveJsonColumnData(array $data, ?Model $model): array
    {
        if (! $model) {
            return $data;
        }

        // Find all attribute names whose type is 'json' or 'object'
        $jsonCols = [];
        foreach ($model->getAttributes()->all() as $name => $attribute) {
            $type = $attribute->getType()->value;
            if (in_array($type, ['json', 'jsonb', 'object'], true)) {
                $jsonCols[] = $name;
            }
        }

        // For each row, json_encode those columns if they exist and are arrays/objects
        return array_map(function (array $row) use ($jsonCols) {
            foreach ($jsonCols as $col) {
                if (array_key_exists($col, $row) && is_array($row[$col])) {
                    $row[$col] = json_encode($row[$col]);
                }
            }

            return $row;
        }, $data);
    }
}
