<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\LCS;
use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\DTOs\ReadPayload;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

class ReadPayloadBuilder
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function build(array $payload): ReadPayload
    {
        $model = $this->context->get('model');
        $readPayload = new ReadPayload($model->getName());

        $tableName = $model->getTableName();

        // Check for a soft delete key and add it to the payload if it exists.
        if ($model->hasDeleteKey()) {
            $readPayload->deleteColumn = $tableName.'.'.$model->getDeleteKey()->getName();
        }

        $this->preparePaginationForDto($payload, $readPayload);
        $primaryKeyName = $model->getPrimaryKey()->getName();
        $primaryKeyAttributeKey = $tableName.'.'.$primaryKeyName;
        $this->prepareSortsForDto($payload, $readPayload, $primaryKeyAttributeKey);

        // Handle sort JOINs for alias attributes that reference relationships
        $this->prepareSortJoins($readPayload);

        // Transfer filters from the incoming payload to the prepared payload.
        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            // Previously we initialized this with $payload['filters']
            $readPayload->filters = Filters::fromArray($payload['filters'])->toArray();
        }

        // Set the allowed scopes for the query.
        $readPayload->scopes = $this->context->get('query')->getAllowedScopes()->toArray();

        // Prepare attributes for the SELECT clause
        $attributes = [];
        $expand = [];

        // Get all attributes from the query
        $queryAttributes = $this->context->get('query')->getAttributes();

        // Add each attribute to the attributes array
        foreach ($queryAttributes as $attribute) {

            // if the attribute dependsOn some other attribute
            if ($attribute->getDepAttributes()->isNotEmpty()) {
                $dependOnAttributes = $attribute->getDepAttributes()->all();
                if (! in_array($dependOnAttributes, $attributes)) {
                    $attributes = array_merge($attributes, $dependOnAttributes);
                }
            }

            // if the attribute dependsOn some other relationship
            if ($attribute->getDepRelationships()->isNotEmpty()) {
                $dependOnRelationships = $attribute->getDepRelationships()->all();
                if (! in_array($dependOnRelationships, $attributes)) {
                    $expand = array_merge($expand, $dependOnRelationships);
                }
            }

            // Skip transformKey attributes as they are post-query transformations
            if ($attribute->isTransformKey()) {
                continue;
            }

            $attributeName = $attribute->getName();

            // For alias attributes with source, use the source
            if ($attribute->isAliasKey() && $attribute->hasAliasSource()) {
                $aliasSource = $attribute->getAliasSource();

                // If alias source doesn't contain a dot, it needs table qualification
                if (! str_contains($aliasSource, '.')) {
                    $aliasSource = $tableName.'.'.$aliasSource;
                }

                $attributes[] = $aliasSource.' AS '.$attributeName;
            }
            // For normal attributes, just use the table-qualified name
            else {
                $attributes[] = $tableName.'.'.$attributeName;
            }
        }

        // set the primary key attribute if it doesn't exists (table-qualified)
        $tableQualifiedPrimaryKey = $tableName.'.'.$primaryKeyName;
        if (! in_array($tableQualifiedPrimaryKey, $attributes) && ! in_array($primaryKeyName, $attributes)) {
            $attributes[] = $tableQualifiedPrimaryKey;
        }

        // Only set attributes if we have any
        if (! empty($attributes)) {
            $readPayload->attributes = $attributes;
        }

        // Log attributes after preparation
        $logger = LCS::getLogger();
        $logger->notice('Attributes prepared for read payload', [
            'type' => 'readPayloadBuilder',
            'modelName' => $model->getName(),
            'tableName' => $tableName,
            'attributes' => $attributes,
        ]);

        // Handle relationship expansion, using payload's expand if present, otherwise default from query.
        if (isset($payload['expand']) && ! empty($payload['expand'])) {
            $expand = array_merge($expand, $payload['expand']);
        } else {
            $expand = array_merge($expand, $this->context->get('query')->getExpand()->toArray());

            if (empty($expand)) {

                $relationshipKeys = $model->getRelationships()->keys()->all();
                if (! empty($relationshipKeys)) {
                    $expand = array_merge($expand, $relationshipKeys);
                }
            }
        }

        if (! empty($expand)) {
            $readPayload->expand = $expand;
        }

        return $readPayload;
    }

    /**
     * Handles sort JOINs for alias attributes that reference relationships.
     *
     * @param  ReadPayload  $readPayload  The payload being prepared
     */
    private function prepareSortJoins(ReadPayload $readPayload): void
    {
        if (empty($readPayload->sorts)) {
            return;
        }

        $logger = LCS::getLogger();
        $model = $this->context->get('model');
        $query = $this->context->get('query');

        // Log sorts array before processing
        $logger->info('Processing sorts - before alias resolution', [
            'type' => 'sortJoins',
            'modelName' => $model->getName(),
            'sorts' => $readPayload->sorts,
        ]);

        $joins = [];
        $relationshipsJoined = []; // Track which relationships we've already joined

        // Process each sort to check for alias attributes with relationship sources
        foreach ($readPayload->sorts as &$sort) {
            $attributeName = $sort['attribute'];

            // Skip if already table-qualified (like primary key sorts)
            if (str_contains($attributeName, '.')) {
                continue;
            }

            // Try to resolve alias attribute to relationship source
            $queryAttributes = $query->getAttributes()->all();
            $resolvedSource = $this->resolveAliasToRelationshipSource($attributeName, $queryAttributes);

            if ($resolvedSource) {
                // Get the registry manager
                $registryManager = $this->context->get('lcs')->getRegistryManager();

                // Get the target table name
                $targetTableName = $this->getTargetTableNameFromPath($resolvedSource['relationshipPath'], $model, $registryManager);
                $tableQualifiedAttribute = $targetTableName.'.'.$resolvedSource['finalAttribute'];

                // Extract relationship path and build JOINs
                $relationshipJoins = $this->buildJoinsForRelationshipPath($resolvedSource['relationshipPath'], $model, $registryManager, $relationshipsJoined);
                $joins = array_merge($joins, $relationshipJoins);

                // Transform sort attribute to use actual table.column
                $sort['attribute'] = $tableQualifiedAttribute;
            }
        }

        // Add joins to payload if any were generated
        if (! empty($joins)) {
            $readPayload->joins = $joins;
        }

        // Log sorts array and joins after processing
        $logger->notice('Processing sorts - after alias resolution', [
            'type' => 'sortJoins',
            'modelName' => $model->getName(),
            'sorts' => $readPayload->sorts,
            'joins' => $readPayload->joins,
        ]);
    }
}
