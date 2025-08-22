<?php

namespace LCSEngine\Tasks;

use LCSEngine\Database\DatabaseOperationsCollection;
use LCSEngine\Database\QueryContext;
use LCSEngine\Schemas\Model\Relationships\HasMany;
use LCSEngine\Schemas\Model\Relationships\HasOne;
use LCSEngine\StateMachine\ContextInterface;

class CreateEntityTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'create_entity';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        $model = $this->context->get('lcs')->getRegistryManager()->get('model', $taskArgs['modelName']);
        // dd("queryPayload::>", $model->getName());
        $queryPayload = $this->preparePayloadForCreate($taskArgs, $model);
        $contextData = $input['payload'];

        // Initialize DB Operator Collection
        $dbOps = new DatabaseOperationsCollection($this->operator);
        if (! empty($contextData)) {
            $createdContext = QueryContext::create($contextData);
            $dbOps->setContext($createdContext);
        }

        // Set registry manager
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());
        $dbOps->add($queryPayload);

        $response = $dbOps->execute($this->operator);
        $parentRecord = $response[0]['result'][0];

        $relatedResults = [];
        $relationships = $model->getRelationships();

        foreach ($relationships as $relationName => $relationship) {
            if ($relationship instanceof HasMany || $relationship instanceof HasOne) {

                // Expect a single child‐record payload, not an array
                // $childData = $contextData[$relationName] ?? null;
                // $childData = $contextData ?? null;
                $childData = $taskArgs ?? null;
                if (! is_array($childData)) {
                    continue;
                }

                // Inject the parent’s key into the child
                $childData[$relationship->getForeignKey()]
                    = $parentRecord[$relationship->getLocalKey()];

                // Prepare & execute the child insert
                $childModel = $this->context->get('lcs')->getRegistryManager()->get('model', $relationship->getRelatedModelName());
                $childQuery = $this->preparePayloadForCreate($childData, $childModel);
                // dd($childData, $childModel, $childQuery);
                $dbOps->add($childQuery);

                $childResponse = $dbOps->execute($this->operator);

                // Store the single created record under the relation’s key
                $relatedResults[$relationName] = $childResponse[0]['result'][0];
            }
        }

        $parentRecord['relatedResults'] = $relatedResults;

        return ['result' => $parentRecord];
    }

    public function preparePayloadForCreate(array $payload, $model): array
    {
        // $model = $this->context->get('lcs')->getRegistryManager()->get('model', $payload['modelName']);
        $registryManager = $this->context->get('lcs')->getRegistryManager();
        $preparedPayload = [
            'type' => 'insert',
            'modelName' => $model->getName(),
        ];

        $defaultGenerator = $registryManager->get('generator', 'default');
        $attributes = $model->getAttributes()->all();
        $dbOps = new DatabaseOperationsCollection($this->operator);
        $dbOps->setRegistryManager($registryManager);

        foreach ($attributes as $attributeName => $attribute) {
            // If the attribute already exists in payload, keep it
            if (isset($payload[$attributeName])) {
                $preparedPayload['data'][0][$attributeName] = $payload[$attributeName];

                continue;
            }

            // Check if the attribute has a generation rule
            if (! empty($attribute->getGenerators())) {
                foreach ($attribute->getGenerators()->all() as $generator) {
                    $generation = $generator->toArray();
                    $generation['payload'] = $payload;

                    // Only process the generation if the current operation is included in the operations list
                    if (! in_array('insert', $generator->getOperations()->map(fn ($operation) => $operation->value)->all())) {
                        continue;
                    }

                    // if (isset($generation->source)) {
                    if ($generator->getSource() !== null) {
                        $sourceKey = $generator->getSource();
                        // $sourceValue = null;
                        $sourceValue = $payload[$sourceKey] ?? null;

                        if ($sourceValue) {
                            $generation['sourceValue'] = $sourceValue;
                        }
                    }

                    $generation['dbOps'] = $dbOps;
                    $generation['dbOperator'] = $this->operator;
                    $generation['modelName'] = $model->getName();
                    $generation['attributeName'] = $attributeName;

                    $generatedValue = $defaultGenerator->generate(
                        $generator->getType()->value,
                        $generation // Convert any extra options to array
                    );

                    if ($generatedValue !== null) {
                        $preparedPayload['data'][0][$attributeName] = $generatedValue;
                    }
                }
            }
        }

        return $preparedPayload;
    }
}
