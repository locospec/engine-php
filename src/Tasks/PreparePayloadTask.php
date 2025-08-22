<?php

namespace LCSEngine\Tasks;

use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\PayloadBuilders\AggregatePayloadBuilder;
use LCSEngine\Tasks\PayloadBuilders\CreatePayloadBuilder;
use LCSEngine\Tasks\PayloadBuilders\DeletePayloadBuilder;
use LCSEngine\Tasks\PayloadBuilders\ReadPayloadBuilder;
use LCSEngine\Tasks\PayloadBuilders\UpdatePayloadBuilder;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

/**
 * Prepares the payload for database operations based on the current action in the state machine.
 * This task transforms the incoming payload into a structured format that the database operator can execute.
 */
class PreparePayloadTask extends AbstractTask implements TaskInterface
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    /**
     * Gets the name of the task.
     */
    public function getName(): string
    {
        return 'prepare_payload';
    }

    /**
     * Sets the context for the task.
     *
     * @param  ContextInterface  $context  The state machine context.
     */
    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    /**
     * Executes the task, dispatching to the appropriate payload preparation method based on the action.
     *
     * @param  array  $payload  The incoming payload.
     * @param  array  $taskArgs  Additional arguments for the task.
     * @return array The original payload and the prepared payload.
     */
    public function execute(array $payload, array $taskArgs = []): array
    {
        $preparedPayload = [];
        // Move user permissions from global context to a dedicated key for locospec.
        if (isset($payload['globalContext']['userPermissions'])) {
            $payload['locospecPermissions']['userPermissions'] = $payload['globalContext']['userPermissions'];
            unset($payload['globalContext']['userPermissions']);
        }

        // Determine the appropriate payload preparation method based on the action from the context.
        switch ($this->context->get('action')) {
            case '_create':
                $createPayloadBuilder = new CreatePayloadBuilder($this->context);
                $preparedPayload = $createPayloadBuilder->build($payload, $this->operator)->toArray();
                break;

            case '_update':
                $createPayloadBuilder = new UpdatePayloadBuilder($this->context);
                $preparedPayload = $createPayloadBuilder->build($payload, $this->operator)->toArray();
                break;

            case '_delete':
                $deletePayloadBuilder = new DeletePayloadBuilder($this->context);
                $preparedPayload = $deletePayloadBuilder->build($payload, $this->operator)->toArray();
                break;

            case '_read':
                $readPayloadBuilder = new ReadPayloadBuilder($this->context);
                $preparedPayload = $readPayloadBuilder->build($payload)->toArray();
                break;

            case '_aggregate':
                $aggregatePayloadBuilder = new AggregatePayloadBuilder($this->context);
                $preparedPayload = $aggregatePayloadBuilder->build($payload)->toArray();
                break;

            case '_read_one':
                $preparedPayload = $this->preparePayloadForReadOne($payload);
                break;

            case '_config':
                $preparedPayload = $this->preparePayloadForReadOne($payload);
                break;

            case '_read_relation_options':
                $preparedPayload = $this->preparePayloadForReadOptions($payload);
                break;

            default:
                break;
        }

        return [
            'payload' => $payload,
            'preparedPayload' => $preparedPayload,
        ];
    }

    /**
     * Prepares the payload for reading relationship options, typically for UI elements like dropdowns.
     *
     * @param  array  $payload  The incoming payload containing relation information.
     * @return array The prepared payload for reading options.
     */
    public function preparePayloadForReadOptions(array $payload): array
    {
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $optionsModel = $registryManager->get('model', $payload['relation']);

        $preparedPayload = [
            'type' => 'select',
            'purpose' => 'read_options',
            'modelName' => $optionsModel->getName(),
        ];

        // Check for a soft delete key on the options model.
        $hasDeleteKey = $optionsModel->hasDeleteKey();

        if ($hasDeleteKey) {
            $preparedPayload['deleteColumn'] = $optionsModel->getDeleteKey()->getName();
        }

        $this->preparePagination($payload, $preparedPayload);

        $tableName = $optionsModel->getTableName();
        $primaryKeyAttributeKey = $tableName.'.'.$optionsModel->getPrimaryKey()->getName();
        $this->prepareSorts($payload, $preparedPayload, $primaryKeyAttributeKey);

        if (isset($payload['filters']) && ! empty($payload['filters'])) {
            // Process filters, adjusting attribute paths to be relative to the relationship.
            // Read options is called when we want to filter a model by it's relationships. And generate dropdown options.
            if (isset($payload['filters']['conditions'])) {
                foreach ($payload['filters']['conditions'] as &$condition) {
                    if (isset($condition['attribute'])) {
                        $attributePath = explode('.', $condition['attribute']);
                        // Get all relationships from the model
                        $relationships = $optionsModel->getRelationships()->keys()->all();

                        // Check if any relationship exists in the path
                        foreach ($relationships as $relationship) {
                            $relationshipIndex = array_search($relationship, $attributePath);
                            if ($relationshipIndex !== false) {
                                // Get the part of the path after the relationship
                                $attributePath = array_slice($attributePath, $relationshipIndex);
                                $condition['attribute'] = implode('.', $attributePath);
                                break;
                            }
                        }
                    }
                }
            }

            $preparedPayload['filters'] = $payload['filters'];
        }

        // Process scopes, ensuring they are valid within the global context.
        if (! empty($payload['scopes']) && ! empty($payload['globalContext'])) {
            $scopes = [];
            foreach ($payload['scopes'] as $scope) {
                if (isset($payload['globalContext'][$scope])) {
                    $scopes[] = $scope;
                }
            }
            $preparedPayload['scopes'] = $scopes;
        }

        return $preparedPayload;
    }

    /**
     * Prepares the payload for reading a single record by its primary key.
     *
     * @param  array  $payload  The incoming payload containing the primary key.
     * @return array The prepared payload for the read_one operation.
     */
    public function preparePayloadForReadOne(array $payload): array
    {
        $preparedPayload = [];
        // Ensure a primary key is provided before preparing the payload.
        if (isset($payload['primaryKey']) && ! empty($payload['primaryKey'])) {
            $preparedPayload = [
                'type' => 'select',
                'purpose' => 'read_one',
                'modelName' => $this->context->get('model')->getName(),
            ];

            // Handle relationship expansion, using payload's expand if present, otherwise expand all relationships.
            if (isset($payload['expand']) && ! empty($payload['expand'])) {
                $preparedPayload['expand'] = $payload['expand'];
            } else {
                $preparedPayload['expand'] = $this->context->get('query')->getExpand()->toArray();
            }

            $preparedPayload['filters'] = [
                'op' => 'and',
                'conditions' => [
                    [
                        'attribute' => $this->context->get('model')->getPrimaryKey()->getName(),
                        'op' => 'is',
                        'value' => $payload['primaryKey'],
                    ],
                ],
            ];
        }

        return $preparedPayload;
    }
}
