<?php

namespace LCSEngine\Schemas\Common;

use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Schemas\Model\Relationships\Relationship;

class JoinColumnHelper
{
    /**
     * Extract join column names and types from a relationship
     *
     * @param  Relationship  $relationship  The relationship to analyze
     * @param  Model  $currentModel  The current model (left side of join)
     * @param  Model  $relatedModel  The related model (right side of join)
     * @return array ['left_column' => string, 'right_column' => string, 'left_col_type' => string, 'right_col_type' => string]
     */
    public static function getJoinColumns(Relationship $relationship, Model $currentModel, Model $relatedModel): array
    {
        // Determine join columns based on relationship type
        if ($relationship instanceof BelongsTo) {
            $leftColumn = $relationship->getForeignKey();   // current.foreign_key
            $rightColumn = $relationship->getOwnerKey();    // related.primary_key
        } elseif ($relationship instanceof HasMany || $relationship instanceof HasOne) {
            $leftColumn = $relationship->getLocalKey();     // current.primary_key
            $rightColumn = $relationship->getForeignKey();  // related.foreign_key
        } else {
            throw new \RuntimeException('Unsupported relationship type: '.get_class($relationship));
        }

        // Get column types for type casting
        $leftColType = $currentModel->getAttribute($leftColumn)->getType()->value;
        $rightColType = $relatedModel->getAttribute($rightColumn)->getType()->value;

        return [
            'left_column' => $leftColumn,
            'right_column' => $rightColumn,
            'left_col_type' => $leftColType,
            'right_col_type' => $rightColType,
        ];
    }

    /**
     * Build complete join structure from a relationship
     *
     * @param  Relationship  $relationship  The relationship to analyze
     * @param  Model  $currentModel  The current model (left side of join)
     * @param  Model  $relatedModel  The related model (right side of join)
     * @param  string  $joinType  The type of join ('inner', 'left', 'right')
     * @param  string|null  $leftTableName  Optional override for left table name
     * @param  string|null  $rightTableName  Optional override for right table name
     * @return array Complete join structure
     */
    public static function buildJoin(
        Relationship $relationship,
        Model $currentModel,
        Model $relatedModel,
        string $joinType = 'inner',
        ?string $leftTableName = null,
        ?string $rightTableName = null
    ): array {
        // Get column information
        $columns = self::getJoinColumns($relationship, $currentModel, $relatedModel);

        // Use provided table names or get from models
        $leftTableName = $leftTableName ?? $currentModel->getTableName();
        $rightTableName = $rightTableName ?? $relatedModel->getTableName();

        // Build full column references
        $leftCol = $leftTableName.'.'.$columns['left_column'];
        $rightCol = $rightTableName.'.'.$columns['right_column'];

        return [
            'type' => $joinType,
            'table' => $rightTableName,
            'on' => [$leftCol, '=', $rightCol],
            'left_col_type' => $columns['left_col_type'],
            'right_col_type' => $columns['right_col_type'],
        ];
    }
}
