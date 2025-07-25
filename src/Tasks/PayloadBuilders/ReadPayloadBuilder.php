<?php

namespace LCSEngine\Tasks\PayloadBuilders;

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
            if ($attribute->getDependsOnAttributes()->isNotEmpty()) {
                $dependOnAttributes = $attribute->getDependsOnAttributes()->all();
                if (! in_array($dependOnAttributes, $attributes)) {
                    $attributes = array_merge($attributes, $dependOnAttributes);
                }
            }

            // if the attribute dependsOn some other relationship
            if ($attribute->getDependsOnRelationships()->isNotEmpty()) {
                $dependOnRelationships = $attribute->getDependsOnRelationships()->all();
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
                $attributes[] = $attribute->getAliasSource().' AS '.$attributeName;
            }
            // For normal attributes, just use the table-qualified name
            else {
                $attributes[] = $tableName.'.'.$attributeName;
            }
        }

        // set the primary key attribute if it doesn't exists
        if (! in_array($primaryKeyName, $attributes)) {
            $attributes[] = $primaryKeyName;
        }

        // Only set attributes if we have any
        if (! empty($attributes)) {
            $readPayload->attributes = $attributes;
        }

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
}
