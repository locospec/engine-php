<?php

namespace LCSEngine\Tasks\PayloadBuilders;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Schemas\Model\Attributes\OperationType;
use LCSEngine\StateMachine\ContextInterface;
use LCSEngine\Tasks\DTOs\CreatePayload;
use LCSEngine\Tasks\Traits\PayloadPreparationHelpers;

class CreatePayloadBuilder
{
    use PayloadPreparationHelpers;

    protected ContextInterface $context;

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function build(array $payload, $operator): CreatePayload
    {
        $model = $this->context->get('model');
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $defaultGenerator = $registryManager->get('generator', 'default');

        $createPayload = new CreatePayload($model->getName());

        $attributes = $this->context->get('mutator')->getAttributes()->filter(fn ($attribute) => ! ($attribute->isAliasKey() || $attribute->isTransformKey()))->all();
        $dbOps = new DatabaseOperationsCollection($operator);
        $dbOps->setRegistryManager($registryManager);

        // Iterate through model attributes to process data and handle generated values.
        foreach ($attributes as $attributeName => $attribute) {
            // If the attribute already exists in payload, keep it
            if (isset($payload[$attributeName])) {
                $createPayload->setData($attributeName, $payload[$attributeName]);

                continue;
            }

            if ($attribute->hasGenerators()) {
                $generators = $attribute->getGenerators()->all();
                foreach ($generators as $generator) {
                    if (! in_array(OperationType::INSERT, $generator->getOperations()->all())) {
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
                        $createPayload->setData($attributeName, $generatedValue);
                    }
                }
            }
        }

        return $createPayload;
    }
}
