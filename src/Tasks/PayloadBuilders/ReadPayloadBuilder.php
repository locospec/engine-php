<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\LCS;
use LCSEngine\Schemas\Common\Filters\Filters;
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
        $registryManager = $this->context->get('lcs')->getRegistryManager();

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
            // TODO: Do this only for transformKey
            if ($attribute->getDepAttributes()->isNotEmpty()) {
                // Loop through each dependent attribute and qualify it
                foreach ($attribute->getDepAttributes()->all() as $depAttributeName) {
                    // Get qualified name for the dependent attribute
                    $depAttributeInfo = $model->getQualifiedAttributeName($depAttributeName, $registryManager);
                    $attributes[] = $depAttributeInfo['qualified'];
                }
            }

            // if the attribute dependsOn some other relationship
            if ($attribute->getDepRelationships()->isNotEmpty()) {
                // Loop through each dependent relationship
                foreach ($attribute->getDepRelationships()->all() as $depRelationship) {
                    $expand[] = $depRelationship;
                }
            }

            // Skip transformKey attributes as they are post-query transformations
            if ($attribute->isTransformKey()) {
                continue;
            }

            $attributeName = $attribute->getName();

            // Use the centralized qualification logic
            $attributeInfo = $model->getQualifiedAttributeName($attributeName, $registryManager);

            // For alias attributes, add AS clause
            if ($attributeInfo['isAlias']) {
                $attributes[] = $attributeInfo['qualified'].' AS '.$attributeName;
            } else {
                $attributes[] = $attributeInfo['qualified'];
            }
        }

        // set the primary key attribute if it doesn't exists (table-qualified)
        $tableQualifiedPrimaryKey = $tableName.'.'.$primaryKeyName;
        if (! in_array($tableQualifiedPrimaryKey, $attributes) && ! in_array($primaryKeyName, $attributes)) {
            $attributes[] = $tableQualifiedPrimaryKey;
        }

        // Only set attributes if we have any
        if (! empty($attributes)) {
            // Remove duplicate attributes
            $readPayload->attributes = array_values(array_unique($attributes));
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
            // Remove duplicate relationships
            $readPayload->expand = array_values(array_unique($expand));
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
        $registryManager = $this->context->get('lcs')->getRegistryManager();

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

            try {
                // Use the model's getQualifiedAttributeName to resolve the attribute
                // This returns comprehensive information about the attribute
                // It handles all cases: simple attributes, aliases, already qualified, and relationships
                $attributeInfo = $model->getQualifiedAttributeName($attributeName, $registryManager);

                if (! $attributeInfo['isSqlExpression']) {
                    // Update sort attribute with qualified name
                    $sort['attribute'] = $attributeInfo['qualified'];
                }

                // If this involves a relationship, we need to add joins
                if ($attributeInfo['isRelationship'] && $attributeInfo['relationshipPath']) {
                    // Get joins for the relationship path
                    $relationshipJoins = $model->getJoinsTo($attributeInfo['relationshipPath'], $registryManager);
                    if ($relationshipJoins) {
                        $joins = array_merge($joins, $relationshipJoins);
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // If attribute doesn't exist in model, skip it
                // This shouldn't happen with valid queries, but we handle it gracefully
                continue;
            }
        }

        // Add joins to payload if any were generated
        if (! empty($joins)) {
            $readPayload->joins = $joins;
        }

        // Log sorts array and joins after processing
        $logger->info('Processing sorts - after alias resolution', [
            'type' => 'sortJoins',
            'modelName' => $model->getName(),
            'sorts' => $readPayload->sorts,
            'joins' => $readPayload->joins,
        ]);
    }
}
