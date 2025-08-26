<?php

namespace LCSEngine\Schemas\Model\Aggregates;

use LCSEngine\LCS;
use LCSEngine\Logger;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Common\JoinColumnHelper;
use LCSEngine\Schemas\Model\Model;

class AggregateProcessor
{
    private Model $model;

    private RegistryManager $registryManager;

    private ?Logger $logger = null;

    public function __construct(Model $model, RegistryManager $registryManager)
    {
        $this->model = $model;
        $this->registryManager = $registryManager;
        $this->logger = LCS::getLogger();

        $this->logger?->info('AggregateProcessor initialized', ['modelName' => $model->getName()]);
    }

    /**
     * Process an aggregate by name and return necessary components
     *
     * @return array{
     *     aggregate: Aggregate,
     *     selectColumns: array,
     *     joins: array,
     *     groupBy: array,
     *     sorts: array
     * }
     */
    public function process(string $aggregateName): array
    {
        $this->logger?->info('Processing aggregate', [
            'modelName' => $this->model->getName(),
            'aggregateName' => $aggregateName,
        ]);

        // Find the aggregate by name on the model
        $aggregate = $this->findAggregateByName($aggregateName);

        // First, analyze all attributes in groupBy and columns to find relationships
        $allPaths = $this->collectAllPaths($aggregate);

        // Prepare table names and aliases for all relationships
        $pathInfo = $this->preparePathInfo($allPaths);

        // Prepare selectColumns with proper aliases
        $selectColumns = $this->prepareSelectColumns($aggregate, $pathInfo);

        // Prepare joins following the existing pattern
        $joins = $this->prepareJoins($pathInfo);

        // Prepare groupBy fields with table names
        $groupBy = $this->prepareGroupBy($aggregate, $pathInfo);

        // Prepare sorts based on groupBy fields with aliases for stable cursor pagination
        $sorts = $this->prepareSorts($aggregate, $pathInfo);

        $result = [
            'aggregate' => $aggregate,
            'selectColumns' => $selectColumns,
            'joins' => $joins,
            'groupBy' => $groupBy,
            'sorts' => $sorts,
        ];

        $this->logger?->info('Aggregate processing completed', [
            'modelName' => $this->model->getName(),
            'aggregateName' => $aggregateName,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Find aggregate by name on the model
     */
    private function findAggregateByName(string $aggregateName): Aggregate
    {
        $aggregate = $this->model->getAggregate($aggregateName);

        if (! $aggregate) {
            $this->logger?->error('Aggregate not found', [
                'modelName' => $this->model->getName(),
                'aggregateName' => $aggregateName,
            ]);

            throw new \InvalidArgumentException(
                "Aggregate '{$aggregateName}' not found on model '{$this->model->getName()}'"
            );
        }

        return $aggregate;
    }

    /**
     * Collect all paths from groupBy and column sources
     */
    private function collectAllPaths(Aggregate $aggregate): array
    {
        $paths = [];

        // Collect from groupBy
        foreach ($aggregate->getGroupBy() as $groupByField) {
            $source = $groupByField->getSource();
            if (str_contains($source, '.')) {
                // Extract the relationship path (everything except the last part)
                $parts = explode('.', $source);
                array_pop($parts); // Remove the attribute name
                if (! empty($parts)) {
                    $paths[] = implode('.', $parts);
                }
            }
        }

        // Collect from column sources
        foreach ($aggregate->getColumns() as $column) {
            $source = $column->getSource();
            if ($source && str_contains($source, '.')) {
                $parts = explode('.', $source);
                array_pop($parts); // Remove the attribute name
                if (! empty($parts)) {
                    $paths[] = implode('.', $parts);
                }
            }
        }

        // Remove duplicates
        return array_unique($paths);
    }

    /**
     * Prepare path information including table names and aliases
     */
    private function preparePathInfo(array $paths): array
    {
        $pathInfo = [];
        $processedPaths = [];

        foreach ($paths as $path) {
            $parts = explode('.', $path);
            $currentModel = $this->model;
            $currentTableName = $this->model->getTableName();
            $pathSoFar = '';

            foreach ($parts as $relationshipName) {
                $pathSoFar = $pathSoFar ? $pathSoFar.'.'.$relationshipName : $relationshipName;

                if (isset($processedPaths[$pathSoFar])) {
                    $currentModel = $processedPaths[$pathSoFar]['model'];
                    $currentTableName = $processedPaths[$pathSoFar]['tableName'];

                    continue;
                }

                $relationship = $currentModel->getRelationship($relationshipName);
                if (! $relationship) {
                    throw new \InvalidArgumentException(
                        "Relationship '{$relationshipName}' not found on model '{$currentModel->getName()}'"
                    );
                }

                $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
                if (! $relatedModel) {
                    throw new \InvalidArgumentException(
                        "Related model '{$relationship->getRelatedModelName()}' not found"
                    );
                }

                $relatedTableName = $relatedModel->getTableName();

                $pathInfo[$pathSoFar] = [
                    'relationship' => $relationship,
                    'model' => $relatedModel,
                    'tableName' => $relatedTableName,
                    'parentModel' => $currentModel,
                    'parentTableName' => $currentTableName,
                ];

                $processedPaths[$pathSoFar] = [
                    'model' => $relatedModel,
                    'tableName' => $relatedTableName,
                ];

                $currentModel = $relatedModel;
                $currentTableName = $relatedTableName;
            }
        }

        return $pathInfo;
    }

    /**
     * Prepare select columns with proper aliases
     */
    private function prepareSelectColumns(Aggregate $aggregate, array $pathInfo): array
    {
        $selectColumns = [];
        $mainTableName = $this->model->getTableName();

        // Add groupBy columns
        foreach ($aggregate->getGroupBy() as $groupByField) {
            $source = $groupByField->getSource();
            $alias = $groupByField->getName();

            if (str_contains($source, '.')) {
                $parts = explode('.', $source);
                $attributeName = array_pop($parts);
                $relationshipPath = implode('.', $parts);

                if (isset($pathInfo[$relationshipPath])) {
                    $tableName = $pathInfo[$relationshipPath]['tableName'];

                    // Generate contextual alias if it was auto-generated
                    if ($groupByField->isAutoGenerated()) {
                        $alias = $tableName.'_'.$attributeName;
                        $groupByField->setName($alias);
                    }

                    $selectColumns[] = "{$tableName}.{$attributeName} AS {$alias}";
                }
            } else {
                // Direct attribute on main model
                // Check if this is an alias attribute
                $attribute = $this->model->getAttributes()->get($source);
                if ($attribute && $attribute->isAliasKey() && $attribute->hasAliasSource()) {
                    // Use the alias SQL expression as source
                    $selectColumns[] = "{$attribute->getAliasSource()} AS {$alias}";
                } elseif ($this->isSqlExpression($source)) {
                    // SQL expression - use as-is without table prefix
                    $selectColumns[] = "{$source} AS {$alias}";
                } else {
                    // Regular column - prefix with table name
                    $selectColumns[] = "{$mainTableName}.{$source} AS {$alias}";
                }
            }
        }

        // Add aggregate function columns
        foreach ($aggregate->getColumns() as $column) {
            $function = strtoupper($column->getFunction());
            $source = $column->getSource();
            $name = $column->getName();

            if ($function === 'COUNT' && ! $source) {
                $selectColumns[] = "COUNT(*) AS {$name}";
            } elseif ($source) {
                if (str_contains($source, '.')) {
                    $parts = explode('.', $source);
                    $attributeName = array_pop($parts);
                    $relationshipPath = implode('.', $parts);

                    if (isset($pathInfo[$relationshipPath])) {
                        $tableName = $pathInfo[$relationshipPath]['tableName'];
                        $selectColumns[] = "{$function}({$tableName}.{$attributeName}) AS {$name}";
                    }
                } else {
                    // Direct attribute on main model
                    $selectColumns[] = "{$function}({$mainTableName}.{$source}) AS {$name}";
                }
            }
        }

        return $selectColumns;
    }

    /**
     * Prepare joins following the existing pattern
     */
    private function prepareJoins(array $pathInfo): array
    {
        $joins = [];

        foreach ($pathInfo as $path => $info) {
            $relationship = $info['relationship'];
            $parentModel = $info['parentModel'];
            $relatedModel = $info['model'];
            $parentTableName = $info['parentTableName'];
            $relatedTableName = $info['tableName'];

            // Build complete join using utility with custom table names from pathInfo
            $joins[] = JoinColumnHelper::buildJoin(
                $relationship,
                $parentModel,
                $relatedModel,
                'left',
                $parentTableName,  // Use pre-computed table names
                $relatedTableName
            );
        }

        return $joins;
    }

    /**
     * Prepare groupBy fields with proper table prefixes
     */
    private function prepareGroupBy(Aggregate $aggregate, array $pathInfo): array
    {
        $groupBy = [];
        $mainTableName = $this->model->getTableName();

        foreach ($aggregate->getGroupBy() as $groupByField) {
            $source = $groupByField->getSource();

            if (str_contains($source, '.')) {
                $parts = explode('.', $source);
                $attributeName = array_pop($parts);
                $relationshipPath = implode('.', $parts);

                if (isset($pathInfo[$relationshipPath])) {
                    $tableName = $pathInfo[$relationshipPath]['tableName'];
                    $groupBy[] = "{$tableName}.{$attributeName}";
                }
            } else {
                // Direct attribute on main model
                // For alias attributes and SQL expressions, use the alias name in GROUP BY
                // For regular columns, use table prefix
                $attribute = $this->model->getAttributes()->get($source);
                if (($attribute && $attribute->isAliasKey()) || $this->isSqlExpression($source)) {
                    // Use the alias name in GROUP BY
                    $groupBy[] = $groupByField->getName();
                } else {
                    // Regular column - use with table prefix
                    $groupBy[] = "{$mainTableName}.{$source}";
                }
            }
        }

        return $groupBy;
    }

    /**
     * Prepare sorts based on groupBy fields for stable cursor pagination
     * Uses aliases that match the select columns for proper cursor pagination
     */
    private function prepareSorts(Aggregate $aggregate, array $pathInfo): array
    {
        $sorts = [];

        foreach ($aggregate->getGroupBy() as $groupByField) {
            // Always use the alias for sorting to match the select columns
            $sorts[] = [
                'attribute' => $groupByField->getName(),
                'direction' => 'ASC',
            ];
        }

        return $sorts;
    }

    /**
     * Check if a source string contains SQL expressions
     */
    private function isSqlExpression(string $source): bool
    {
        // Check for common SQL keywords that indicate an expression
        $sqlKeywords = ['CASE', 'WHEN', 'CAST', 'COALESCE', 'CONCAT', 'NULLIF', 'IFNULL', 'IF'];

        foreach ($sqlKeywords as $keyword) {
            if (stripos($source, $keyword) !== false) {
                return true;
            }
        }

        // Also check for operators that might indicate expressions
        if (preg_match('/[\+\-\*\/\|\|]/', $source)) {
            return true;
        }

        return false;
    }
}
