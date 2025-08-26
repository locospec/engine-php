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

/**
 * RelationshipExpanderWithJoinsTrait - Provides JOIN-based expansion methods
 *
 * Required properties when using this trait:
 * - $model (Model)
 * - $dbOps (DatabaseOperationsCollection)
 * - $registryManager (RegistryManager)
 * - $logger (Logger)
 */
trait RelationshipExpanderWithJoinsTrait
{
    /**
     * @var array Array of grouped paths from groupExpandPaths
     */
    private array $pathGroups = [];

    /**
     * @var array Maps each path to its group ID (string)
     */
    private array $pathToGroupMap = [];

    /**
     * Groups expansion paths to avoid cartesian products
     * Also builds the path-to-group mapping for later lookups
     */
    private function groupExpandPaths(array $paths): array
    {
        // Make paths unique first
        $paths = array_unique($paths);

        $groups = [];
        $safeRoots = []; // BelongsTo/HasOne that can be joined
        $pathsByRoot = [];

        // Reset the path-to-group mapping
        $this->pathToGroupMap = [];
        $this->pathGroups = [];

        // Group paths by their root
        foreach ($paths as $path) {
            $parts = explode('.', $path);
            $root = $parts[0];

            if (! isset($pathsByRoot[$root])) {
                $pathsByRoot[$root] = [];
            }
            $pathsByRoot[$root][] = $path;
        }

        // Process each root group
        foreach ($pathsByRoot as $root => $rootPaths) {
            // If this root only has simple path (no dots) and it's safe to join
            if (count($rootPaths) === 1 && ! str_contains($rootPaths[0], '.')) {
                $relationship = $this->model->getRelationship($root);
                if ($relationship instanceof BelongsTo || $relationship instanceof HasOne) {
                    $safeRoots[] = $rootPaths[0];
                } else {
                    // HasMany - must be isolated
                    $groups[] = $rootPaths;
                }
            } else {
                // Has nested paths - keep all paths for this root together
                $groups[] = $rootPaths;
            }
        }

        // Add safe roots as one group if any
        if (! empty($safeRoots)) {
            $groups[] = $safeRoots;
        }

        // Generate unique group IDs and build the mapping
        foreach ($groups as $groupPaths) {
            // Create a unique group ID based on the paths in the group
            $groupId = 'group_'.md5(implode('|', $groupPaths));

            // Store the group with its unique ID
            $this->pathGroups[$groupId] = $groupPaths;

            // Map each path to its group ID
            foreach ($groupPaths as $path) {
                $this->pathToGroupMap[$path] = $groupId;
            }
        }

        return $groups;
    }

    /**
     * Generate JOINs for a single group of paths
     * For single-path groups, generates a WHERE IN query instead of JOINs
     *
     * @param  array  $group  Array of paths like ["city", "city.district", "city.district.state"]
     * @return array ['joins' => [...], 'attributes' => [...], 'tableNames' => [...], 'isSinglePath' => bool]
     */
    private function generateJoinsForGroup(array $group): array
    {
        // For single-path groups, we'll generate a simpler query
        if (count($group) === 1) {
            $path = $group[0];
            $parts = explode('.', $path);
            $relationship = $this->model->getRelationship($parts[0]);
            $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
            $relatedTableName = $relatedModel->getTableName();

            // Get attributes with aliases (same pattern as JOIN)
            $attributes = [];
            foreach ($relatedModel->getAttributesOnly() as $attr) {
                $attributes[] = $relatedTableName.'.'.$attr->getName().' AS '.$relatedTableName.'_'.$attr->getName();
            }

            return [
                'joins' => [], // No joins for single path
                'attributes' => $attributes,
                'tableNames' => [$path => $relatedTableName],
                'relationship' => $relationship,
                'relatedModel' => $relatedModel,
                'isSinglePath' => true,
            ];
        }

        // Multiple paths - generate JOINs
        $joins = [];
        $attributes = [];
        $tableNames = []; // Maps path to table name
        $relationshipsJoined = []; // Track which relationships we've already joined

        // Generate JOINs for each path in the group
        foreach ($group as $path) {
            $parts = explode('.', $path);
            $currentModel = $this->model;
            $currentTableName = $this->model->getTableName();
            $pathSoFar = '';

            foreach ($parts as $relationshipName) {
                // Build path incrementally
                $pathSoFar = $pathSoFar ? $pathSoFar.'.'.$relationshipName : $relationshipName;

                // Skip if we already joined this relationship
                if (isset($relationshipsJoined[$pathSoFar])) {
                    $currentModel = $relationshipsJoined[$pathSoFar]['model'];
                    $currentTableName = $relationshipsJoined[$pathSoFar]['tableName'];

                    continue;
                }

                $relationship = $currentModel->getRelationship($relationshipName);
                $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());

                // Get the related table name
                $relatedTableName = $relatedModel->getTableName();
                $tableNames[$pathSoFar] = $relatedTableName;

                // Build complete join using utility
                $joins[] = JoinColumnHelper::buildJoin(
                    $relationship,
                    $currentModel,
                    $relatedModel,
                    'left'  // LEFT JOIN for optional expansion
                );

                // Add attributes with SQL aliases for this joined table
                foreach ($relatedModel->getAttributesOnly() as $attr) {
                    $attributes[] = $relatedTableName.'.'.$attr->getName().' AS '.$relatedTableName.'_'.$attr->getName();
                }

                // Track that we've joined this relationship
                $relationshipsJoined[$pathSoFar] = [
                    'model' => $relatedModel,
                    'tableName' => $relatedTableName,
                ];

                // Move to next level
                $currentModel = $relatedModel;
                $currentTableName = $relatedTableName;
            }
        }

        return [
            'joins' => $joins,
            'attributes' => $attributes,
            'tableNames' => $tableNames,
            'isSinglePath' => false,
        ];
    }

    /**
     * Execute operation for JOIN query with WHERE IN clause
     * Also handles single-path queries without JOINs
     *
     * @param  array  $results  The main results to expand
     * @param  array  $queryInfo  Query information from generateJoinsForGroup
     * @return array The operation results
     */
    public function executeOperation(array $results, array $queryInfo): array
    {
        if ($queryInfo['isSinglePath']) {
            // Single path - query the related model directly
            $relationship = $queryInfo['relationship'];
            $relatedModel = $queryInfo['relatedModel'];

            // Determine which IDs to fetch based on relationship type
            if ($relationship instanceof BelongsTo) {
                $foreignKey = $relationship->getForeignKey();
                $sourceIds = array_filter(array_column($results, $foreignKey));
                $targetAttribute = $relationship->getOwnerKey();
            } else {
                // HasMany or HasOne
                $primaryKey = $this->model->getPrimaryKey()->getName();
                $sourceIds = array_filter(array_column($results, $primaryKey));
                $targetAttribute = $relationship->getForeignKey();
            }

            if (empty($sourceIds)) {
                return [];
            }

            // Build operation for related model
            $operation = [
                'type' => 'select',
                'purpose' => 'expandSinglePath',
                'modelName' => $relatedModel->getName(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [[
                        'attribute' => $targetAttribute,
                        'op' => 'is_any_of',
                        'value' => array_values(array_unique($sourceIds)),
                    ]],
                ],
                'attributes' => $queryInfo['attributes'],
            ];
        } else {
            // Multiple paths - use JOINs
            $primaryKey = $this->model->getPrimaryKey()->getName();
            $sourceIds = array_filter(array_column($results, $primaryKey));

            if (empty($sourceIds)) {
                return [];
            }

            // Get main model attributes with aliases
            $mainAttributes = [];
            $tableName = $this->model->getTableName();
            foreach ($this->model->getAttributesOnly() as $attr) {
                $mainAttributes[] = $tableName.'.'.$attr->getName().' AS '.$tableName.'_'.$attr->getName();
            }

            // Build operation with JOINs
            $operation = [
                'type' => 'select',
                'purpose' => 'expandWithJoins',
                'modelName' => $this->model->getName(),
                'filters' => [
                    'op' => 'and',
                    'conditions' => [[
                        'attribute' => $tableName.'.'.$primaryKey,
                        'op' => 'is_any_of',
                        'value' => array_values(array_unique($sourceIds)),
                    ]],
                ],
                'joins' => $queryInfo['joins'],
                'attributes' => array_merge($mainAttributes, $queryInfo['attributes']),
            ];
        }

        $results = $this->dbOps->add($operation)->execute();

        return $results;
    }

    /**
     * Get the group ID for a given path
     *
     * @param  string  $path  The expansion path to check
     * @return string|null The group ID if the path belongs to a group, null otherwise
     */
    public function getGroupByPath(string $path): ?string
    {
        return $this->pathToGroupMap[$path] ?? null;
    }

    /**
     * Get all paths in a specific group
     *
     * @param  string  $groupId  The group ID
     * @return array Array of paths in the group
     */
    public function getPathsInGroup(string $groupId): array
    {
        return $this->pathGroups[$groupId] ?? [];
    }

    /**
     * Get all path groups
     *
     * @return array Array of all groups
     */
    public function getPathGroups(): array
    {
        return $this->pathGroups;
    }

    /**
     * Map aliased results back to original results
     *
     * @param  array  $originalResults  The original results array
     * @param  array  $aliasedRows  The rows from query with prefixed columns
     * @param  string  $path  The relationship path being mapped
     * @param  string  $tableName  The table name for this path
     * @return array Updated results with expanded relationships
     */
    public function mapAliasedResults(array $originalResults, array $aliasedRows, string $path, string $tableName): array
    {
        $primaryKey = $this->model->getPrimaryKey()->getName();
        $mainTableName = $this->model->getTableName();
        $mainTablePrefix = $mainTableName.'_';
        $relatedTablePrefix = $tableName.'_';

        // Determine relationship type and get the first relationship
        $parts = explode('.', $path);
        $currentModel = $this->model;
        $isHasMany = false;
        $firstRelationship = null;

        foreach ($parts as $i => $part) {
            $relationship = $currentModel->getRelationship($part);
            if ($i === 0) {
                $firstRelationship = $relationship;
            }
            if ($relationship instanceof HasMany) {
                $isHasMany = true;
            }
            $currentModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
        }

        // Determine the grouping key based on query type
        if (count($aliasedRows) > 0 && isset($aliasedRows[0][$mainTablePrefix.$primaryKey])) {
            // This is a JOIN query - group by main table's primary key
            $groupByKey = $mainTablePrefix.$primaryKey;
            $lookupKey = $primaryKey;
        } else {
            // This is a single-path query - group by foreign key in related table
            if ($firstRelationship instanceof BelongsTo) {
                // For BelongsTo, we group by the owner key
                $groupByKey = $relatedTablePrefix.$firstRelationship->getOwnerKey();
                $lookupKey = $firstRelationship->getForeignKey();
            } else {
                // For HasMany/HasOne, we group by the foreign key
                $groupByKey = $relatedTablePrefix.$firstRelationship->getForeignKey();
                $lookupKey = $primaryKey;
            }
        }

        // Group aliased rows by the appropriate key
        $groupedData = [];
        foreach ($aliasedRows as $row) {
            if (! isset($row[$groupByKey])) {
                continue;
            }

            $groupId = $row[$groupByKey];

            // Extract related record data
            $relatedData = [];
            $hasData = false;
            foreach ($row as $key => $value) {
                if (str_starts_with($key, $relatedTablePrefix)) {
                    $cleanKey = substr($key, strlen($relatedTablePrefix));
                    $relatedData[$cleanKey] = $value;
                    if ($value !== null) {
                        $hasData = true;
                    }
                }
            }

            // Only add if we have actual related data
            if ($hasData) {
                // For uniqueness, create a key based on the primary key of the related model
                $relatedPrimaryKey = $currentModel->getPrimaryKey()->getName();
                $uniqueKey = $relatedData[$relatedPrimaryKey] ?? null;

                if ($uniqueKey !== null) {
                    if (! isset($groupedData[$groupId])) {
                        $groupedData[$groupId] = [];
                    }
                    $groupedData[$groupId][$uniqueKey] = $relatedData;
                }
            }
        }

        // Map back to original results using the full path
        foreach ($originalResults as &$result) {
            $resultId = $result[$lookupKey];
            $relatedRecords = isset($groupedData[$resultId]) ? array_values($groupedData[$resultId]) : [];

            // Set the value at the nested path
            $this->setNestedValue($result, $path, $isHasMany ? $relatedRecords : ($relatedRecords[0] ?? null));
        }

        return $originalResults;
    }

    /**
     * Set a value at a nested path in an array
     *
     * @param  array  &$array  The array to modify
     * @param  string  $path  The dot-separated path
     * @param  mixed  $value  The value to set
     */
    private function setNestedValue(array &$array, string $path, $value): void
    {
        $parts = explode('.', $path);
        $current = &$array;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                // Last part, set the value
                $current[$part] = $value;
            } else {
                // Intermediate part, ensure it exists
                if (! isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
    }
}
