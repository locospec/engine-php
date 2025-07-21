<?php

namespace LCSEngine\Schemas\Common\Filters;

use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;

/**
 * BatchRelationshipResolverTrait provides optimization for filter resolution by grouping
 * conditions that share relationship paths and executing them in batch queries.
 *
 * This trait should be used by RelationshipResolver to add batch resolution capabilities.
 */
trait BatchRelationshipResolverTrait
{
    /**
     * Groups conditions by their relationship paths for optimized batch resolution.
     *
     * This method implements an optimization strategy that reduces the number of database
     * queries by intelligently grouping conditions that share relationship paths.
     *
     * CORE CONCEPT:
     * Instead of resolving each relationship condition independently (N queries for N conditions),
     * we group conditions by their shared relationship paths and execute fewer, more efficient
     * JOIN queries.
     *
     * EXAMPLE:
     * Given an AND filter group with these conditions:
     * 1. name = 'John'                        (main model)
     * 2. age > 25                             (main model)
     * 3. locality.name = 'Mumbai'             (relationship path: locality)
     * 4. locality.city.name = 'Delhi'         (relationship path: locality → city)
     * 5. locality.city.state.name = 'MH'      (relationship path: locality → city → state)
     * 6. events.bids.amount > 1000            (relationship path: events → bids)
     * 7. branches.bank.name = 'HDFC'          (relationship path: branches → bank)
     *
     * CURRENT APPROACH (7 queries):
     * - Query 1: Resolve condition 3 → locality_id IN [...]
     * - Query 2: Resolve condition 4 → locality_id IN [...]
     * - Query 3: Resolve condition 5 → locality_id IN [...]
     * - Query 4: Resolve condition 6 → event_id IN [...]
     * - Query 5: Resolve condition 7 → branch_id IN [...]
     * - Query 6: Main query with all resolved conditions
     *
     * OPTIMIZED APPROACH (3 queries):
     * - Group 1: Main model + locality path
     *   JOIN: properties → localities → cities → states
     *   WHERE: name = 'John' AND age > 25 AND localities.name = 'Mumbai'
     *          AND cities.name = 'Delhi' AND states.name = 'MH'
     *   Returns: property IDs matching all conditions
     *
     * - Group 2: Main model + events path
     *   JOIN: properties → events → bids
     *   WHERE: name = 'John' AND age > 25 AND bids.amount > 1000
     *   Returns: property IDs matching all conditions
     *
     * - Group 3: Main model + branches path
     *   JOIN: properties → branches → banks
     *   WHERE: name = 'John' AND age > 25 AND banks.name = 'HDFC'
     *   Returns: property IDs matching all conditions
     *
     * KEY INSIGHTS:
     * 1. Conditions sharing a relationship path prefix can be evaluated together
     * 2. Main model conditions are included in EVERY group (main model is always in the path)
     * 3. This reduces result sets early by applying main model filters in subqueries
     * 4. The final output is still conditions on the main model (preserves interface)
     *
     * @param  FilterGroup  $group  The filter group to analyze (must be AND or OR operator)
     * @return FilterGroup A FilterGroup containing mix of Conditions and BatchedFilterGroups
     */
    private function groupConditionsByPath(FilterGroup $group): FilterGroup
    {
        // Only process AND/OR groups
        if (! in_array($group->getOperator(), [LogicalOperator::AND])) {
            return $group;
        }

        // Ensure all items are Conditions
        $conditions = $group->getConditions();
        foreach ($conditions as $item) {
            if (! ($item instanceof Condition)) {
                return $group;
            }
        }

        // Early return for trivial cases
        if (count($conditions) === 0) {
            return $group;
        }

        // Separate main model and relationship conditions
        $mainModelConditions = [];
        $pathGroups = [];

        foreach ($conditions as $condition) {
            $parts = explode('.', $condition->getAttribute());

            if (count($parts) === 1) {
                // Main model condition
                $mainModelConditions[] = $condition;
            } else {
                // Group by first relationship
                $rootPath = $parts[0];
                $pathGroups[$rootPath][] = $condition;
            }
        }

        // Single main model condition with no relationships - return original
        if (count($mainModelConditions) === 1 && empty($pathGroups)) {
            return $group;
        }

        // Build optimized result with same operator as original
        $resultGroup = new FilterGroup($group->getOperator());

        if ($group->getOperator() == LogicalOperator::OR) {
            foreach ($mainModelConditions as $condition) {
                $resultGroup->add($condition);
            }
        }

        // Create BatchedFilterGroup for each relationship path
        foreach ($pathGroups as $rootPath => $pathConditions) {
            $batchedGroup = new BatchedFilterGroup($rootPath, $group->getOperator());

            // Include main model conditions in each batch
            foreach ($mainModelConditions as $condition) {
                $batchedGroup->add($condition);
            }

            // Add relationship conditions
            foreach ($pathConditions as $condition) {
                $batchedGroup->add($condition);
            }

            // If only one condition total, no benefit from batching
            if (count($batchedGroup->getConditions()) === 1) {
                $resultGroup->add($batchedGroup->getConditions()[0]);
            } else {
                $resultGroup->add($batchedGroup);
            }
        }

        // Handle standalone main model conditions
        if (! empty($mainModelConditions) && empty($pathGroups)) {
            // Multiple main model conditions - batch them
            $mainBatch = new BatchedFilterGroup('', $group->getOperator());
            foreach ($mainModelConditions as $condition) {
                $mainBatch->add($condition);
            }
            $resultGroup->add($mainBatch);
        }

        // Log the transformation
        $this->logger->debug('Batch relationship resolver - grouped conditions', [
            'type' => 'batchRelationshipResolver',
            'operation' => 'groupConditionsByPath',
            'originalGroup' => $group->toArray(),
            'originalConditionCount' => count($conditions),
            'resultGroup' => $resultGroup->toArray(),
            'resultItemCount' => count($resultGroup->getConditions()),
        ]);

        return $resultGroup;
    }

    /**
     * Resolves a BatchedFilterGroup into a single condition on the main model.
     *
     * This method takes a group of conditions that share a relationship path and executes
     * them together in a single optimized query with JOINs. Works for both AND and OR groups.
     *
     * @param  BatchedFilterGroup  $batchedGroup  The batched group to resolve
     * @return Condition A single condition on the main model's primary key
     */
    private function resolveBatchedGroup(BatchedFilterGroup $batchedGroup): Condition
    {
        $sharedPath = $batchedGroup->getSharedPath();
        $primaryKey = $this->model->getPrimaryKey()->getName();

        // Log the batch resolution attempt
        $this->logger->debug('Resolving batched group', [
            'type' => 'batchRelationshipResolver',
            'operation' => 'resolveBatchedGroup',
            'sharedPath' => $sharedPath,
            'conditionCount' => count($batchedGroup->getConditions()),
            'filters' => $batchedGroup->toArray(),
        ]);

        // Main model conditions only (empty shared path)
        if (empty($sharedPath)) {
            // Query main model directly
            $selectOp = [
                'type' => 'select',
                'purpose' => 'resolveBatchMainConditions',
                'modelName' => $this->model->getName(),
                'filters' => $batchedGroup->toArray(),
                'attributes' => [$primaryKey],
            ];

            $this->logger->info('Batch resolver - main model query', [
                'type' => 'batchRelationshipResolver',
                'selectOp' => $selectOp,
            ]);

            $results = $this->dbOps->add($selectOp)->execute();
            $matchingIds = array_column($results[0]['result'], $primaryKey);

            return new Condition(
                $primaryKey,
                ComparisonOperator::IS_ANY_OF,
                $matchingIds
            );
        }

        // Relationship conditions - build JOINs
        return $this->resolveBatchRelationshipConditions($sharedPath, $batchedGroup);
    }

    /**
     * Resolves batch conditions that involve relationships using JOINs.
     */
    private function resolveBatchRelationshipConditions(string $sharedPath, BatchedFilterGroup $batchedGroup): Condition
    {
        $conditions = $batchedGroup->getConditions();
        $primaryKey = $this->model->getPrimaryKey()->getName();

        // Find the deepest relationship path
        $deepestPath = [];
        foreach ($conditions as $condition) {
            $parts = explode('.', $condition->getAttribute());
            if (count($parts) > 1) {
                array_pop($parts); // Remove attribute, keep only relationships
                if (count($parts) > count($deepestPath)) {
                    $deepestPath = $parts;
                }
            }
        }

        // Build JOINs based on the deepest path
        $joins = [];
        $currentModel = $this->model;

        foreach ($deepestPath as $relationshipName) {
            $relationship = $currentModel->getRelationship($relationshipName);
            $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

            // Determine join columns based on relationship type
            if ($relationship instanceof BelongsTo) {
                $leftColumn = $relationship->getForeignKey();
                $rightColumn = $relationship->getOwnerKey();
            } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
                $leftColumn = $relationship->getLocalKey();
                $rightColumn = $relationship->getForeignKey();
            } else {
                throw new \RuntimeException('Unsupported relationship type: '.get_class($relationship));
            }

            $joins[] = [
                'type' => 'inner',
                'table' => $relatedModel->getTableName(),
                'on' => [
                    $currentModel->getTableName().'.'.$leftColumn,
                    '=',
                    $relatedModel->getTableName().'.'.$rightColumn,
                ],
            ];

            $currentModel = $relatedModel;
        }

        // Transform conditions to use table names instead of relationship paths
        // Preserve the original operator (BATCHED_AND -> AND, BATCHED_OR -> OR)
        $operator = match ($batchedGroup->getOperator()) {
            LogicalOperator::BATCHED_AND => LogicalOperator::AND,
            LogicalOperator::BATCHED_OR => LogicalOperator::OR,
            default => LogicalOperator::AND
        };
        $transformedGroup = new FilterGroup($operator);

        foreach ($conditions as $condition) {
            $parts = explode('.', $condition->getAttribute());

            if (count($parts) === 1) {
                // Main model attribute - add table prefix
                $newAttribute = $this->model->getTableName().'.'.$parts[0];
            } else {
                // Relationship attribute - resolve to get the target model
                $attribute = array_pop($parts); // Get attribute name

                // Navigate through relationships to find the target model
                $targetModel = $this->model;
                foreach ($parts as $relationshipName) {
                    $relationship = $targetModel->getRelationship($relationshipName);
                    $targetModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
                }

                $newAttribute = $targetModel->getTableName().'.'.$attribute;
            }

            $transformedGroup->add(new Condition(
                $newAttribute,
                $condition->getOperator(),
                $condition->getValue()
            ));
        }

        // Build the select operation
        $selectOp = [
            'type' => 'select',
            'purpose' => 'resolveBatchRelationshipConditions',
            'modelName' => $this->model->getName(),
            'joins' => $joins,
            'filters' => $transformedGroup->toArray(),
            'attributes' => [$this->model->getTableName().'.'.$primaryKey],
        ];

        $this->logger->debug('Batch resolver - relationship query', [
            'type' => 'batchRelationshipResolver',
            'sharedPath' => $sharedPath,
            'joinCount' => count($joins),
            'selectOp' => $selectOp,
        ]);

        $results = $this->dbOps->add($selectOp)->execute();
        $matchingIds = array_column($results[0]['result'], $primaryKey);

        return new Condition(
            $primaryKey,
            ComparisonOperator::IS_ANY_OF,
            $matchingIds
        );
    }
}
