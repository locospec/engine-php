<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\DTOs\AggregatePayload;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

class AggregatePayloadBuilder
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function build(array $payload): AggregatePayload
    {
        $model = $this->context->get('model');
        $aggregatePayload = new AggregatePayload($model->getName());

        if (isset($payload['aggregate'])) {
            $aggregatePayload->aggregate = $payload['aggregate'];
        }

        // Handle options attribute - look up its optionsAggregator
        if (isset($payload['options'])) {
            $attributeName = $payload['options'];
            $attribute = $model->getAttribute($attributeName);

            if ($attribute && $attribute->getOptionsAggregator()) {
                $aggregatePayload->aggregate = $attribute->getOptionsAggregator();
            }
        }

        $tableName = $model->getTableName();

        // Check for a soft delete key and add it to the payload if it exists.
        if ($model->hasDeleteKey()) {
            $aggregatePayload->deleteColumn = $tableName.'.'.$model->getDeleteKey()->getName();
        }

        $this->preparePaginationForDto($payload, $aggregatePayload);
        $primaryKeyAttributeKey = $tableName.'.'.$model->getPrimaryKey()->getName();
        $this->prepareSortsForDto($payload, $aggregatePayload, $primaryKeyAttributeKey);

        // Transfer filters from the incoming payload to the prepared payload.
        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            $aggregatePayload->filters = Filters::fromArray($payload['filters'])->toArray();
        }

        // Set the allowed scopes for the query.
        $aggregatePayload->scopes = $this->context->get('query')->getAllowedScopes()->toArray();

        return $aggregatePayload;
    }
}
