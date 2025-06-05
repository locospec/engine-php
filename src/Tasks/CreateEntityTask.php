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
        $preparedPayload = [
            'type' => 'insert',
            'modelName' => $model->getName(),
        ];

        $generator = $this->context->get('generator');
        $attributes = $model->getAttributes()->getAttributes();
        $dbOps = new DatabaseOperationsCollection($this->operator);
        $dbOps->setRegistryManager($this->context->get('lcs')->getRegistryManager());

        foreach ($attributes as $attributeName => $attribute) {
            // If the attribute already exists in payload, keep it
            if (isset($payload[$attributeName])) {
                $preparedPayload['data'][0][$attributeName] = $payload[$attributeName];

                continue;
            }

            // Check if the attribute has a generation rule
            if (! empty($attribute->getGenerations())) {
                foreach ($attribute->getGenerations() as $generation) {
                    $generation->payload = $payload;
                    // Only process the generation if the current operation is included in the operations list
                    if (isset($generation->operations) && is_array($generation->operations)) {
                        if (! in_array('insert', $generation->operations)) {
                            continue;
                        }
                    }

                    if (isset($generation->source)) {
                        $sourceKey = $generation->source;
                        $sourceValue = $payload[$sourceKey] ?? null;

                        if ($sourceValue) {
                            $generation->sourceValue = $sourceValue;
                        }
                    }

                    $generation->dbOps = $dbOps;
                    $generation->dbOperator = $this->operator;
                    $generation->modelName = $model->getName();
                    $generation->attributeName = $attributeName;

                    $generatedValue = $generator->generate(
                        $generation->type,
                        (array) $generation // Convert any extra options to array
                    );

                    if ($generatedValue !== null) {
                        $preparedPayload['data'][0][$attributeName] = $generatedValue;
                    }
                }
            }
        }

        // dd("preparedPayload", $preparedPayload);
        return $preparedPayload;
    }
}
