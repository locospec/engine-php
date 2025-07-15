<?php

namespace LCSEngine\Schemas\Model\Filters;

/**
 * BatchedFilterGroup represents a group of conditions that share a relationship path
 * and can be resolved together in a single query.
 *
 * This extends FilterGroup to maintain compatibility while adding path-specific metadata
 * for batch resolution optimization.
 */
class BatchedFilterGroup extends FilterGroup
{
    private string $sharedPath;

    public function __construct(string $sharedPath = '', LogicalOperator $operator = LogicalOperator::AND)
    {
        // Convert regular operators to batched versions
        $batchedOperator = match ($operator) {
            LogicalOperator::AND => LogicalOperator::BATCHED_AND,
            LogicalOperator::OR => LogicalOperator::BATCHED_OR,
            default => $operator
        };

        parent::__construct($batchedOperator);
        $this->sharedPath = $sharedPath;
    }

    public function getSharedPath(): string
    {
        return $this->sharedPath;
    }
}
