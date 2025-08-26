<?php

namespace LCSEngine\Schemas\Common\Filters;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Logger;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Common\JoinColumnHelper;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Relationship;

class RelationshipResolver
{
    use BatchRelationshipResolverTrait;

    private Model $model;

    private DatabaseOperationsCollection $dbOps;

    private RegistryManager $registryManager;

    private Logger $logger;

    public function __construct(
        Model $model,
        DatabaseOperationsCollection $dbOps,
        RegistryManager $registryManager,
        Logger $logger
    ) {
        $this->model = $model;
        $this->dbOps = $dbOps;
        $this->registryManager = $registryManager;
        $this->logger = $logger;
    }

    public function resolve(Filters $filters): Filters
    {
        $root = $filters->getRoot();

        $this->logger->debug('Resolve Relationships', [
            'filters' => $filters->toArray(),
        ]);

        if ($root instanceof Condition) {
            return new Filters($this->resolveCondition($root));
        }

        if ($root instanceof FilterGroup) {
            return new Filters($this->resolveGroup($root));
        }

        if ($root instanceof PrimitiveFilterSet) {
            return new Filters($this->resolvePrimitiveSet($root));
        }

        return $filters;
    }

    /**
     * Converts a condition with relationship paths into a condition on the main model.
     *
     * Example: "locality.city.state.name = 'California'"
     * becomes: "locality_id IN [1,2,3]" (keys from main model records that match)
     */
    private function resolveCondition(Condition $condition): Condition
    {
        $path = explode('.', $condition->getAttribute());

        // If no relationships (single attribute), return as-is
        if (count($path) === 1) {
            return $condition;
        }

        // Split path: relationships + attribute to filter on
        // Example: "locality.city.state.name" -> relationships: [locality, city, state], filterAttribute: name
        $filterAttribute = array_pop($path);
        $relationshipPath = $path;

        // Determine which key from main model connects to the relationship chain
        $firstRelationship = $this->model->getRelationship($relationshipPath[0]);

        // This is not a relationship, it's just a regular attribute
        if (is_null($firstRelationship)) {
            return $condition;
        }

        if ($firstRelationship instanceof BelongsTo) {
            // Main model has foreign key (e.g., properties.locality_id)
            $mainModelKey = $firstRelationship->getForeignKey();      // properties.locality_id
            $relatedModelKey = $firstRelationship->getOwnerKey();     // localities.id
        } elseif ($firstRelationship instanceof HasMany || $firstRelationship instanceof HasOne) {
            // Related model has foreign key (e.g., posts.user_id pointing to users.id)
            $mainModelKey = $firstRelationship->getLocalKey();       // users.id
            $relatedModelKey = $firstRelationship->getForeignKey();  // posts.user_id
        } else {
            throw new \RuntimeException('Unsupported relationship type');
        }

        // OPTIMIZATION: Single relationship doesn't need JOINs
        // Example: "locality.name = 'Mumbai'" can be resolved directly
        if (count($relationshipPath) === 1) {
            $relatedModel = $this->registryManager->get('model', $firstRelationship->getRelatedModelName());

            // Query the related model directly to get its primary keys
            //
            // IMPORTANT: We extract different columns based on relationship type:
            //
            // Example 1 - BelongsTo (Properties belongs to Locality):
            //   - Filter: "locality.name = 'Mumbai'"
            //   - Query: SELECT id FROM localities WHERE name = 'Mumbai'
            //   - relatedModelKey = 'id' (locality's primary key)
            //   - Returns: [1, 2, 3] (locality IDs)
            //   - Final condition: properties.locality_id IN [1, 2, 3]
            //   - Why: We need locality IDs because properties.locality_id stores locality's ID
            //
            // Example 2 - HasMany (User has many Posts):
            //   - Filter: "posts.title = 'Hello'"
            //   - Query: SELECT user_id FROM posts WHERE title = 'Hello'
            //   - relatedModelKey = 'user_id' (foreign key in posts)
            //   - Returns: [10, 20, 30] (user IDs from posts table)
            //   - Final condition: users.id IN [10, 20, 30]
            //   - Why: We need to extract user_id from posts to match against users.id
            //
            // The key insight: relatedModelKey is what connects back to the main model
            // - BelongsTo: Extract primary key (id) from related model
            // - HasMany: Extract foreign key (user_id) from related model

            $selectOp = [
                'type' => 'select',
                'purpose' => 'resolveSingleCondition',
                'modelName' => $relatedModel->getName(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [
                        [
                            'attribute' => $filterAttribute,
                            'op' => $condition->getOperator()->value,
                            'value' => $condition->getValue(),
                        ],
                    ],
                ],
                'attributes' => [$relatedModelKey],
            ];

            $this->logger->info('Relationship resolver', [
                'type' => 'relationshipResolver',
                'operation' => 'singleRelationship',
                'selectOp' => $selectOp,
            ]);

            $results = $this->dbOps->add($selectOp)->execute();
            $matchingIds = array_column($results[0]['result'], $relatedModelKey);

            // Return condition on main model's foreign key
            return new Condition(
                $mainModelKey,
                ComparisonOperator::IS_ANY_OF,
                $matchingIds
            );
        }

        // MULTIPLE RELATIONSHIPS: Need JOINs to traverse the chain
        // Example: "locality.city.state.name = 'California'"
        $joins = [];
        $currentModel = $this->model;  // Start with main model

        foreach ($relationshipPath as $relationshipName) {
            $relationship = $currentModel->getRelationship($relationshipName);
            $joinModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

            // Determine JOIN columns based on relationship type
            //
            // Example path: "locality.city.state.name = 'California'"
            // We need to JOIN: properties -> localities -> cities -> states
            //
            // Iteration 1: currentModel = properties, joinModel = localities
            // - Relationship: Properties BelongsTo Locality
            // - currentModelColumn = 'locality_id' (foreign key in properties)
            // - joinModelColumn = 'id' (primary key in localities)
            // - JOIN: properties JOIN localities ON properties.locality_id = localities.id
            //
            // Iteration 2: currentModel = localities, joinModel = cities
            // - Relationship: Locality BelongsTo City
            // - currentModelColumn = 'city_id' (foreign key in localities)
            // - joinModelColumn = 'id' (primary key in cities)
            // - JOIN: localities JOIN cities ON localities.city_id = cities.id
            //
            // For HasMany example: "posts.comments.content = 'Great!'"
            // - Relationship: User HasMany Posts
            // - currentModelColumn = 'id' (primary key in users)
            // - joinModelColumn = 'user_id' (foreign key in posts)
            // - JOIN: users JOIN posts ON users.id = posts.user_id
            //
            // The pattern: We always join on the relationship's defined keys
            // - BelongsTo: current's foreign key = join's primary key
            // - HasMany: current's primary key = join's foreign key

            // Build complete join using utility  
            $joins[] = JoinColumnHelper::buildJoin($relationship, $currentModel, $joinModel, 'inner');

            // Move to next model in chain
            $currentModel = $joinModel;
        }

        // Execute query with JOINs
        //
        // Example: "locality.city.state.name = 'California'"
        // Generated SQL:
        //   SELECT properties.locality_id
        //   FROM properties
        //   JOIN localities ON properties.locality_id = localities.id
        //   JOIN cities ON localities.city_id = cities.id
        //   JOIN states ON cities.state_id = states.id
        //   WHERE states.name = 'California'
        //
        // Result: All locality_id values from properties where the chain matches
        // Final condition: properties.locality_id IN [extracted values]
        //
        // Note: $currentModel is now the last model in the chain (states in this example)
        $selectOp = [
            'type' => 'select',
            'purpose' => 'resolveJoinCondition',
            'modelName' => $this->model->getName(),
            'joins' => $joins,
            'filters' => [
                'op' => 'and',
                'conditions' => [
                    [
                        'attribute' => $currentModel->getTableName().'.'.$filterAttribute,
                        'op' => $condition->getOperator()->value,
                        'value' => $condition->getValue(),
                    ],
                ],
            ],
            'attributes' => [$this->model->getTableName().'.'.$mainModelKey],
        ];

        $this->logger->notice('Relationship resolver', [
            'type' => 'relationshipResolver',
            'operation' => 'multipleRelationships',
            'selectOp' => $selectOp,
        ]);

        $results = $this->dbOps->add($selectOp)->execute();
        $matchingKeys = array_column($results[0]['result'], $mainModelKey);

        // Return condition on main model's key
        return new Condition(
            $mainModelKey,
            ComparisonOperator::IS_ANY_OF,
            $matchingKeys
        );
    }

    private function resolveGroup(FilterGroup $group): FilterGroup
    {
        $resolvedGroup = new FilterGroup($group->getOperator());

        // If $group's first condition is a Condition, then it would mean all of them are conditions

        $afterGrouping = $this->groupConditionsByPath($group);

        // Check conditions, following same chain, including current model also, call resolveBatchJoinCondition, add the new condition to $group, remove old conditions

        foreach ($afterGrouping->getConditions() as $condition) {
            if ($condition instanceof Condition) {
                $resolved = $this->resolveCondition($condition);
                $resolvedGroup->add($resolved);
            } elseif ($condition instanceof BatchedFilterGroup) {
                // Handle batched groups separately
                $resolvedGroup->add($this->resolveBatchedGroup($condition));
            } elseif ($condition instanceof FilterGroup) {
                $resolvedGroup->add($this->resolveGroup($condition));
            } elseif ($condition instanceof PrimitiveFilterSet) {
                $resolvedGroup->add($this->resolvePrimitiveSet($condition));
            }
        }

        // $this->logger->notice(
        //     'Final Resolved Group',
        //     [
        //         'resolvedGroup' => $resolvedGroup->toArray(),
        //     ],
        // );

        return $resolvedGroup;
    }

    private function resolvePrimitiveSet(PrimitiveFilterSet $set): PrimitiveFilterSet
    {
        $resolvedSet = new PrimitiveFilterSet;

        foreach ($set->getFilters() as $key => $value) {
            $condition = new Condition($key, ComparisonOperator::IS, $value);
            $resolved = $this->resolveCondition($condition);

            $resolvedSet->add(
                $resolved->getAttribute(),
                $resolved->getValue()
            );
        }

        return $resolvedSet;
    }

    private function getExtractAndPointAttributes(Relationship $relationship): array
    {
        if ($relationship instanceof BelongsTo) {
            return [
                'extract' => $relationship->getOwnerKey(),
                'point' => $relationship->getForeignKey(),
                'operator' => ComparisonOperator::IS_ANY_OF,
            ];
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            return [
                'extract' => $relationship->getForeignKey(),
                'point' => $relationship->getLocalKey(),
                'operator' => ComparisonOperator::IS_ANY_OF,
            ];
        }

        throw new \RuntimeException('Unsupported relationship type: '.get_class($relationship));
    }
}
