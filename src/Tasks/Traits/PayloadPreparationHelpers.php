<?php

namespace LCSEngine\Tasks\Traits;

use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Common\JoinColumnHelper;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\Tasks\DTOs\Interfaces\PaginatablePayloadInterface;
use LCSEngine\Tasks\DTOs\Interfaces\SortablePayloadInterface;

trait PayloadPreparationHelpers
{
    /**
     * Handles pagination logic for read operations.
     *
     * @param  array  $payload  The incoming payload.
     * @param  object|array  $preparedPayload  The payload being prepared (DTO or array).
     */
    private function preparePagination(array $payload, array &$preparedPayload): void
    {
        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload['pagination'] = $payload['pagination'];
        }
    }

    /**
     * Handles sorting logic, ensuring the primary key is always included as a final sort criterion for stable ordering.
     *
     * @param  array  $payload  The incoming payload.
     * @param  array  $preparedPayload  The payload being prepared (passed by reference).
     * @param  string  $primaryKeyAttributeKey  The name of the primary key attribute.
     */
    private function prepareSorts(array $payload, array &$preparedPayload, string $primaryKeyAttributeKey): void
    {
        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload['sorts'] = $payload['sorts'];

            // Check if primary key exists in sorts
            $primaryKeyExists = false;
            foreach ($payload['sorts'] as $sort) {
                if (isset($sort['attribute']) && $sort['attribute'] === $primaryKeyAttributeKey) {
                    $primaryKeyExists = true;
                    break;
                }
            }

            // Add primary key to end of sorts if it doesn't exist
            if (! $primaryKeyExists) {
                $preparedPayload['sorts'][] = [
                    'attribute' => $primaryKeyAttributeKey,
                    'direction' => 'ASC',
                ];
            }
        } else {
            $preparedPayload['sorts'] = [[
                'attribute' => $primaryKeyAttributeKey,
                'direction' => 'ASC',
            ]];
        }
    }

    /**
     * Handles pagination logic for read operations using a DTO.
     *
     * @param  array  $payload  The incoming payload.
     * @param  ReadPayload  $preparedPayload  The payload being prepared.
     */
    private function preparePaginationForDto(array $payload, PaginatablePayloadInterface $preparedPayload): void
    {
        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload->pagination = $payload['pagination'];
        }
    }

    /**
     * Handles sorting logic for a DTO, ensuring the primary key is always included as a final sort criterion for stable ordering.
     *
     * @param  array  $payload  The incoming payload.
     * @param  ReadPayload  $preparedPayload  The payload being prepared.
     * @param  string  $primaryKeyAttributeKey  The name of the primary key attribute.
     */
    private function prepareSortsForDto(array $payload, SortablePayloadInterface $preparedPayload, string $primaryKeyAttributeKey): void
    {
        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload->sorts = $payload['sorts'];

            // Check if primary key exists in sorts
            $primaryKeyExists = false;
            foreach ($payload['sorts'] as $sort) {
                if (isset($sort['attribute']) && $sort['attribute'] === $primaryKeyAttributeKey) {
                    $primaryKeyExists = true;
                    break;
                }
            }

            // Add primary key to end of sorts if it doesn't exist
            if (! $primaryKeyExists) {
                $preparedPayload->sorts[] = [
                    'attribute' => $primaryKeyAttributeKey,
                    'direction' => 'ASC',
                ];
            }
        } else {
            $preparedPayload->sorts = [[
                'attribute' => $primaryKeyAttributeKey,
                'direction' => 'ASC',
            ]];
        }
    }

    /**
     * Resolves an alias attribute to its relationship source if it references relationships.
     * Pure function that only depends on the provided attributes.
     *
     * @param  string  $attributeName  The attribute name to resolve
     * @param  array  $attributes  Array of Attribute objects
     * @return array|null Array with relationshipPath and tableQualifiedAttribute, or null if not a relationship alias
     */
    protected function resolveAliasToRelationshipSource(string $attributeName, array $attributes): ?array
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === $attributeName && $attribute->isAliasKey() && $attribute->hasAliasSource()) {
                $source = $attribute->getAliasSource();

                // Check if source contains relationship path (has dots)
                if (str_contains($source, '.')) {
                    $parts = explode('.', $source);
                    $finalAttribute = array_pop($parts); // Remove final attribute
                    $relationshipPath = $parts; // Remaining parts are the relationship path

                    if (! empty($relationshipPath)) {
                        return [
                            'relationshipPath' => $relationshipPath,
                            'finalAttribute' => $finalAttribute,
                            'source' => $source,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Gets the table name of the target model by following a relationship path.
     * Pure function that navigates through relationships.
     *
     * @param  array  $relationshipPath  Array of relationship names
     * @param  Model  $baseModel  The starting model
     * @param  RegistryManager  $registryManager  Registry manager to resolve models
     * @return string The table name of the target model
     */
    protected function getTargetTableNameFromPath(array $relationshipPath, Model $baseModel, RegistryManager $registryManager): string
    {
        $currentModel = $baseModel;

        foreach ($relationshipPath as $relationshipName) {
            $relationship = $currentModel->getRelationship($relationshipName);
            $relatedModelName = $relationship->getRelatedModelName();
            $currentModel = $registryManager->get('model', $relatedModelName);
        }

        return $currentModel->getTableName();
    }

    /**
     * Builds JOINs for a relationship path using pure logic.
     *
     * @param  array  $relationshipPath  Array of relationship names
     * @param  Model  $baseModel  The starting model
     * @param  RegistryManager  $registryManager  Registry manager to resolve models
     * @param  array  &$relationshipsJoined  Reference to track joined relationships (modified)
     * @return array Array of JOIN structures
     */
    protected function buildJoinsForRelationshipPath(array $relationshipPath, Model $baseModel, RegistryManager $registryManager, array &$relationshipsJoined): array
    {
        $joins = [];
        $currentModel = $baseModel;
        $pathSoFar = '';

        foreach ($relationshipPath as $relationshipName) {
            $pathSoFar = $pathSoFar ? $pathSoFar.'.'.$relationshipName : $relationshipName;

            // Skip if we already joined this relationship path
            if (isset($relationshipsJoined[$pathSoFar])) {
                $currentModel = $relationshipsJoined[$pathSoFar]['model'];

                continue;
            }

            $relationship = $currentModel->getRelationship($relationshipName);
            $relatedModel = $registryManager->get('model', $relationship->getRelatedModelName());

            // Build complete join using utility
            $joins[] = JoinColumnHelper::buildJoin($relationship, $currentModel, $relatedModel, 'left');

            // Track joined relationship
            $relationshipsJoined[$pathSoFar] = [
                'model' => $relatedModel,
                'tableName' => $relatedModel->getTableName(),
            ];

            // Move to next model in chain
            $currentModel = $relatedModel;
        }

        return $joins;
    }

    /**
     * Determines if an attribute source should be prefixed with a table name.
     * Returns false for SQL expressions that shouldn't have table prefixes.
     *
     * @param  string  $source  The attribute source to check
     * @return bool True if should be table-prefixed, false otherwise
     */
    protected function shouldPrefixTableName(string $source): bool
    {
        // If already contains a dot (table.column), don't prefix
        if (str_contains($source, '.')) {
            return false;
        }

        // Check if this is a CASE expression FIRST
        if (preg_match('/^CASE\s+/i', $source)) {
            return false;
        }

        // Check if this is an aggregate function (COUNT, SUM, AVG, MIN, MAX)
        if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $source)) {
            return false;
        }

        // Check for other SQL expressions (CAST, COALESCE, CONCAT, etc.)
        if (preg_match('/^(CAST|COALESCE|CONCAT|NULLIF|IFNULL|IF)\s*\(/i', $source)) {
            return false;
        }

        // Check for mathematical expressions or other SQL patterns
        if (preg_match('/^[A-Z_]+\s*\(/i', $source)) {
            return false;
        }

        // If none of the above patterns match, it's likely a simple column name
        // that should be table-prefixed
        return true;
    }
}
