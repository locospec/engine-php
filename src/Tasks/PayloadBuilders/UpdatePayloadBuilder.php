<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Schemas\Common\Filters\ComparisonOperator;
use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\Schemas\Common\Filters\LogicalOperator;
use LCSEngine\Schemas\Model\Attributes\OperationType;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\DTOs\UpdatePayload;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

class UpdatePayloadBuilder
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function build(array $payload, $operator): UpdatePayload
    {
        $model = $this->context->get('model');
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $defaultGenerator = $registryManager->get('generator', 'default');

        $updatePayload = new UpdatePayload($model->getName());

        if (isset($payload['filters'])) {
            $updatePayload->filters = Filters::fromArray($payload['filters'])->toArray();
        } else {
            $primaryKey = $model->getPrimaryKey()->getName();
            $group = Filters::group(LogicalOperator::AND)->add(Filters::condition($primaryKey, ComparisonOperator::IS, $payload[$primaryKey]));
            $filters = new Filters($group);
            $updatePayload->filters = $filters->toArray();
        }

        $attributes = $this->context->get('mutator')->getAttributes()->filter(fn ($attribute) => ! $attribute->isAliasKey())->all();
        $dbOps = new DatabaseOperationsCollection($operator);
        $dbOps->setRegistryManager($registryManager);

        // Iterate through model attributes to process data and handle generated values.
        foreach ($attributes as $attributeName => $attribute) {
            // If the attribute already exists in payload, keep it
            if (isset($payload[$attributeName])) {
                $updatePayload->setData($attributeName, $payload[$attributeName]);

                continue;
            }

            if ($attribute->hasGenerators()) {
                $generators = $attribute->getGenerators()->all();
                foreach ($generators as $generator) {
                    if (! in_array(OperationType::UPDATE, $generator->getOperations()->all())) {
                        continue;
                    }

                    $generatorPayload = $generator->toArray();
                    $generatorPayload['payload'] = $payload;
                    $generatorPayload['dbOps'] = $dbOps;
                    $generatorPayload['dbOperator'] = $operator;
                    $generatorPayload['modelName'] = $model->getName();
                    $generatorPayload['attributeName'] = $attributeName;
                    $generatorPayload['value'] = $generator->getValue();

                    if ($generator->hasSource()) {
                        $sourceKey = $generator->getSource();
                        $sourceValue = $payload[$sourceKey] ?? null;

                        if ($sourceValue) {
                            $generatorPayload['sourceValue'] = $sourceValue;
                        }
                    }

                    $generatedValue = $defaultGenerator->generate(
                        $generator->getType()->value,
                        $generatorPayload
                    );

                    if ($generatedValue !== null) {
                        $updatePayload->setData($attributeName, $generatedValue);
                    }
                }
            }
        }

        return $updatePayload;
    }
}
