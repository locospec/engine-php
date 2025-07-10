<?php

namespace LCSEngine\Tasks\PayloadBuilders;

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

        // Check for a soft delete key and add it to the payload if it exists.
        if ($model->hasDeleteKey()) {
            $readPayload->deleteColumn = $model->getDeleteKey()->getName();
        }

        $this->preparePaginationForDto($payload, $readPayload);

        $primaryKeyAttributeKey = $model->getPrimaryKey()->getName();
        $this->prepareSortsForDto($payload, $readPayload, $primaryKeyAttributeKey);

        // Transfer filters from the incoming payload to the prepared payload.
        if (isset($payload['filters']) && !empty($payload['filters'])) {
            $readPayload->filters = $payload['filters'];
        }

        // Set the allowed scopes for the query.
        $readPayload->scopes = $this->context->get('query')->getAllowedScopes()->toArray();

        // Handle relationship expansion, using payload's expand if present, otherwise default from query.
        if (isset($payload['expand']) && !empty($payload['expand'])) {
            $readPayload->expand = $payload['expand'];
        } else {
            $readPayload->expand = $this->context->get('query')->getExpand()->toArray();
        }

        return $readPayload;
    }
}
