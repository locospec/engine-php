<?php

namespace Locospec\Engine\Actions\Model;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Query\FilterGroup;

/**
 * Validates inputs for model actions
 */
class ModelActionValidator
{
    /**
     * Validate create action input
     */
    public function validateCreate(array $input, ModelDefinition $model): void
    {
        if (empty($input)) {
            throw new InvalidArgumentException('Create action requires input data');
        }

        // Additional model-specific validation can be added here
    }

    /**
     * Validate update action input
     */
    public function validateUpdate(array $input, ModelDefinition $model): void
    {
        if (! isset($input['conditions'])) {
            throw new InvalidArgumentException('Update action requires conditions');
        }

        if (! isset($input['data']) || ! is_array($input['data'])) {
            throw new InvalidArgumentException('Update action requires data array');
        }
    }

    /**
     * Validate delete action input
     */
    public function validateDelete(array $input, ModelDefinition $model): void
    {
        if (! isset($input['conditions'])) {
            throw new InvalidArgumentException('Delete action requires conditions');
        }
    }

    /**
     * Validate read one action input
     */
    public function validateReadOne(array $input, ModelDefinition $model): void
    {
        if (! isset($input['filters'])) {
            throw new InvalidArgumentException('ReadOne action requires filters');
        }
    }

    /**
     * Validate read list action input
     */
    public function validateReadList(array $input, ModelDefinition $model): void
    {
        // Pagination validation
        if (isset($input['pagination'])) {
            if (isset($input['pagination']['page']) && $input['pagination']['page'] < 1) {
                throw new InvalidArgumentException('Page number must be greater than 0');
            }
            if (isset($input['pagination']['per_page']) && $input['pagination']['per_page'] < 1) {
                throw new InvalidArgumentException('Items per page must be greater than 0');
            }
        }

        // Sort validation
        if (isset($input['sorts'])) {
            foreach ($input['sorts'] as $sort) {
                if (! isset($sort['attribute'])) {
                    throw new InvalidArgumentException('Sort must specify an attribute');
                }
                if (isset($sort['direction']) && ! in_array(strtolower($sort['direction']), ['asc', 'desc'])) {
                    throw new InvalidArgumentException("Invalid sort direction. Use 'asc' or 'desc'");
                }
            }
        }
    }

    /**
     * Convert conditions to FilterGroup if needed
     */
    public function normalizeConditions(array $input): array
    {
        if (isset($input['conditions']) && ! ($input['conditions'] instanceof FilterGroup)) {
            $input['conditions'] = FilterGroup::fromArray([
                'operator' => 'and',
                'conditions' => is_array($input['conditions']) ?
                    $input['conditions'] : [$input['conditions']],
            ]);
        }

        return $input;
    }
}
