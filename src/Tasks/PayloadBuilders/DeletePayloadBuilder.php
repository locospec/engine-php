<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Schemas\Common\Filters\ComparisonOperator;
use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\Schemas\Common\Filters\LogicalOperator;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\DTOs\DeletePayload;
use LCSEngine\Tasks\DTOs\ReadPayload;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

class DeletePayloadBuilder
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function build(array $payload, $operator): DeletePayload
    {
        $model = $this->context->get('model');
        $softDelete = $model->getConfig()->getSoftDelete();
        $primaryKey = $model->getPrimaryKey()->getName();

        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $dbOps = new DatabaseOperationsCollection($operator);
        $dbOps->setRegistryManager($registryManager);

        $deletePayload = new DeletePayload($model->getName());
        $deletePayload->setSoftdelete($softDelete);

        if (isset($payload['filters'])) {
            $deletePayload->filters = Filters::fromArray($payload['filters'])->toArray();
        } else {
            $primaryKey = $model->getPrimaryKey()->getName();
            $group = Filters::group(LogicalOperator::AND)->add(Filters::condition($primaryKey, ComparisonOperator::IS, $payload[$primaryKey]));
            $filters = new Filters($group);
            $deletePayload->filters = $filters->toArray();
        }

        $hasDeleteKey = $model->hasDeleteKey();

        if ($hasDeleteKey) {
            $deletePayload->setDeleteColumn($model->getDeleteKey()->getName());
        }

        // Prepare payloads for any cascade delete operations.
        $cascadePayloads = $this->prepareCascadeDeletePayloads($registryManager, $model->getName(), [$payload['primary_key']], $dbOps, [], $operator);

        $preparedPayload = array_merge([$deletePayload->toArray()], $cascadePayloads);

        $deletePayload->setCascadePayloads($preparedPayload);

        return $deletePayload;
    }

    private function prepareCascadeDeletePayloads(
        $registryManager,
        string $sourceModelName,
        array $sourceIds,
        $dbOps,
        array &$cascadePayloads,
        $operator,
    ): array {
        $sourceModel = $registryManager->get('model', $sourceModelName);
        // Get all 'has_many' relationships to identify records for cascade deletion.
        $hasManyRelationships = $sourceModel->getRelationshipsByType('has_many');

        foreach ($hasManyRelationships as $relationName => $relationship) {
            $targetModelName = $relationship->getRelatedModelName();
            $targetModelForeignKey = $relationship->getForeignKey();
            $targetModelLocalKey = $relationship->getLocalKey();
            $targetModel = $registryManager->get('model', $targetModelName);

            $deletePayload = new DeletePayload($targetModelName);
            $deletePayload->setSoftdelete($targetModel->getConfig()->getSoftDelete());
            $group = Filters::group(LogicalOperator::AND)->add(Filters::condition($targetModelForeignKey, ComparisonOperator::IS_ANY_OF, $sourceIds));
            $filters = new Filters($group);
            $deletePayload->filters = $filters->toArray();
            $hasDeleteKey = $targetModel->hasDeleteKey();

            if ($hasDeleteKey) {
                $deletePayload->setDeleteColumn($targetModel->getDeleteKey()->getName());
            }

            $payload = $deletePayload->toArray();
            unset($payload['cascadePayloads']);
            $cascadePayloads[] = $payload;

            // Get the target model and check for nested relationships
            // Check for nested relationships on the target model to continue the cascade delete recursively.
            $nestedHasManyRelationships = $targetModel->getRelationshipsByType('has_many');

            // If target model has its own has_many relationships, recurse
            if (! empty($nestedHasManyRelationships)) {
                $relatedIds = $this->getRelatedModelIds($targetModelName, $targetModelForeignKey, $sourceIds, $targetModelLocalKey, $dbOps, $operator);

                if (! empty($relatedIds)) {
                    $this->prepareCascadeDeletePayloads($registryManager, $targetModelName, $relatedIds, $dbOps, $cascadePayloads, $operator);
                }
            }
        }

        return $cascadePayloads;
    }

    private function getRelatedModelIds(string $modelName, string $foreignKey, array $parentIds, string $localKey, $dbOps, $operator): array
    {
        $relatedIds = [];
        $readPayload = new ReadPayload($modelName);
        $group = Filters::group(LogicalOperator::AND)->add(Filters::condition($foreignKey, ComparisonOperator::IS_ANY_OF, $parentIds));
        $filters = new Filters($group);
        $readPayload->filters = $filters->toArray();

        $dbOps->add($readPayload->toArray());
        $response = $dbOps->execute($operator);

        if (isset($response[0]['result']) && is_array($response[0]['result']) && ! empty($response[0]['result'])) {
            foreach ($response[0]['result'] as $record) {
                if (isset($record[$localKey])) {
                    $relatedIds[] = $record[$localKey];
                }
            }
        }

        return $relatedIds;
    }
}
