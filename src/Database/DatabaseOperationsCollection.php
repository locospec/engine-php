<?php

namespace LCSEngine\Database;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
use LCSEngine\Logger;
use LCSEngine\Registry\DatabaseDriverInterface;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Common\Filters\AliasResolver;
use LCSEngine\Schemas\Common\Filters\ContextResolver;
use LCSEngine\Schemas\Common\Filters\FilterCleaner;
use LCSEngine\Schemas\Common\Filters\Filters;
use LCSEngine\Schemas\Common\Filters\LogicalOperator;
use LCSEngine\Schemas\Common\Filters\RelationshipExpander;
use LCSEngine\Schemas\Common\Filters\RelationshipResolver;
use LCSEngine\Schemas\Model\Aggregates\AggregateProcessor;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;
use LCSEngine\Schemas\Model\ScopeResolver;
use LCSEngine\SpecValidator;
use RuntimeException;

class DatabaseOperationsCollection
{
    /** @var array[] */
    private array $operations = [];

    private SpecValidator $validator;

    private ValueResolver $valueResolver;

    private ResultTransformation $resultTransformer;

    private ?RegistryManager $registryManager = null;

    private ?QueryContext $context = null;

    private Logger $logger;

    public function __construct()
    {
        $this->validator = new SpecValidator;
        $this->valueResolver = new ValueResolver;
        $this->resultTransformer = new ResultTransformation;
        $this->logger = LCS::getLogger();
        $this->logger->info('DatabaseOperationsCollection initialized', ['type' => 'dbOps']);
    }

    public function setContext(QueryContext $context): self
    {
        $this->context = $context;
        $this->logger->info('Query context set', ['type' => 'dbOps']);

        return $this;
    }

    public function setRegistryManager(RegistryManager $registryManager): self
    {
        $this->registryManager = $registryManager;
        $this->logger->info('RegistryManager set', ['type' => 'dbOps']);

        return $this;
    }

    /**
     * Add a new operation to the collection
     *
     * @param  array  $operation  The operation to add
     *
     * @throws RuntimeException if operation is invalid
     */
    public function add(array $operation): self
    {
        // ToDoRajesh:preparePayload
        // This should be a task: we are preparing the payload here
        $this->logger->info('Adding operation', [
            'type' => 'dbOps',
            'operation' => $operation,
        ]);

        if (! isset($operation['modelName'])) {
            $this->logger->error('Operation missing modelName', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);

            throw new InvalidArgumentException('Operation must specify a modelName');
        }

        $model = $this->registryManager->get('model', $operation['modelName']);

        if (! $model) {
            $this->logger->error('Model not found', [
                'type' => 'dbOps',
                'modelName' => $operation['modelName'],
            ]);
            throw new InvalidArgumentException("Model not found: {$operation['modelName']}");
        }

        if (isset($operation['scopes'])) {
            $this->logger->info('Resolving scopes', [
                'type' => 'dbOps',
                'scopes' => $operation['scopes'],
            ]);

            $resolver = new ScopeResolver($this->registryManager, $operation['modelName']);
            $scopeFilters = $resolver->resolveScopes($operation['scopes']);

            $this->logger->info('Scopes resolved to filters', [
                'type' => 'dbOps',
                'scopeFilters' => $scopeFilters,
            ]);

            // Merge filters more efficiently
            if (isset($operation['filters'])) {
                $filterGroup = Filters::group(LogicalOperator::AND);
                $filterGroup->add(Filters::fromArray($operation['filters'])->getRoot())->add(Filters::fromArray($scopeFilters)->getRoot());
                $filters = new Filters($filterGroup);
                $operation['filters'] = $filters->toArray();
                $this->logger->info('Merged scope filters with existing filters', [
                    'type' => 'dbOps',
                    'mergedFilters' => $operation['filters'],
                ]);
            } else {
                $operation['filters'] = Filters::fromArray($scopeFilters)->toArray();
                $this->logger->info('No existing filters, using scope filters', [
                    'type' => 'dbOps',
                    'filters' => $operation['filters'],
                ]);
            }

            unset($operation['scopes']);
        }

        if (isset($operation['filters']) && $this->context) {
            $this->logger->info('Resolving context in filters', [
                'type' => 'dbOps',
                'filters' => $operation['filters'],
            ]);

            $contextResolver = new ContextResolver($this->context->all());
            $contextResolvedFilters = $contextResolver->resolve(Filters::fromArray($operation['filters']));
            $cleanedFilters = (new FilterCleaner)->clean($contextResolvedFilters);
            // $operation['filters'] = $contextResolvedFilters->toArray();
            $operation['filters'] = $cleanedFilters->toArray();

            $this->logger->debug('Context resolved in filters', [
                'type' => 'dbOps',
                'filters' => $contextResolvedFilters->toArray(),
                'cleanedFilters' => $cleanedFilters->toArray(),
            ]);
        }

        if (empty($operation['filters']['conditions'])) {
            unset($operation['filters']);
        }

        if (isset($operation['filters'])) {
            $this->logger->info('Resolve Aliases in filters', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);

            $aliasResolver = new AliasResolver($model->getAliases());
            $aliasResolved = $aliasResolver->resolve(Filters::fromArray($operation['filters']));
            $operation['filters'] = $aliasResolved->toArray();

            $this->logger->info('Filters aliases resolved', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);

            // if (! isset($operation['joins'])) {
            $relationshipResolver = new RelationshipResolver($model, $this, $this->registryManager, $this->logger);
            $resolvedRelationshipFilters = $relationshipResolver->resolve(Filters::fromArray($operation['filters']));
            $operation['filters'] = $resolvedRelationshipFilters->toArray();
            // }

            $this->logger->info('Relationship filters resolved', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);
        }

        // 4. Resolve any context variables in filter values
        // if (isset($operation['filters']) && $this->context) {
        //     $this->logger->info('Resolving context in filters', [
        //         'type' => 'dbOps',
        //         'filters' => $operation['filters'],
        //     ]);

        //     $contextResolver = new ContextResolver($this->context->all());
        //     $contextResolvedFilters = $contextResolver->resolve(Filters::fromArray($operation['filters']));
        //     $cleanedFilters = (new FilterCleaner)->clean($contextResolvedFilters);
        //     // $operation['filters'] = $contextResolvedFilters->toArray();
        //     $operation['filters'] = $cleanedFilters->toArray();

        //     $this->logger->debug('Context resolved in filters', [
        //         'type' => 'dbOps',
        //         'filters' => $contextResolvedFilters->toArray(),
        //         'cleanedFilters' => $cleanedFilters->toArray(),
        //     ]);
        // }

        // if (empty($operation['filters']['conditions'])) {
        //     unset($operation['filters']);
        // }

        // Process aggregate if it exists
        if (isset($operation['aggregate']) && is_string($operation['aggregate'])) {
            $this->logger->info('Processing aggregate', [
                'type' => 'dbOps',
                'aggregateName' => $operation['aggregate'],
                'modelName' => $operation['modelName'],
            ]);

            $aggregateProcessor = new AggregateProcessor($model, $this->registryManager);
            $aggregateResult = $aggregateProcessor->process($operation['aggregate']);

            // Add aggregate components to the operation
            $operation['attributes'] = $aggregateResult['selectColumns'];

            // Use joins directly (don't merge)
            if (! empty($aggregateResult['joins'])) {
                $operation['joins'] = $aggregateResult['joins'];
            }

            // Add groupBy fields
            if (! empty($aggregateResult['groupBy'])) {
                $operation['groupBy'] = $aggregateResult['groupBy'];
            }

            // Replace sorts with aggregate-specific sorts for stable cursor pagination
            // This overrides any default sorts from the payload builder
            $operation['sorts'] = $aggregateResult['sorts'] ?? [];

            // Remove the aggregate key as it's been processed
            unset($operation['aggregate']);

            $this->logger->info('Aggregate processed', [
                'type' => 'dbOps',
                'attributes' => $operation['attributes'],
                'joins' => $operation['joins'] ?? [],
                'groupBy' => $operation['groupBy'] ?? [],
                'sorts' => $operation['sorts'] ?? [],
            ]);
        }

        // Resolve any context variables in Data for insert
        if (isset($operation['type']) && $operation['type'] === 'insert' && isset($operation['data']) && $this->context) {
            $this->logger->info('Resolving context in data', [
                'type' => 'dbOps',
                'filters' => $operation['data'],
            ]);

            $operation['data'] = $this->valueResolver->resolveData(
                $operation['data'],
                $this->context
            );

            $this->logger->info('Context resolved in data', [
                'type' => 'dbOps',
                'filters' => $operation['data'],
            ]);
        }

        // Resolve any json columns in Data for insert
        if (isset($operation['type']) && $operation['type'] === 'insert' && isset($operation['data'])) {
            $this->logger->info('Resolving json column values in data', [
                'type' => 'dbOps',
                'filters' => $operation['data'],
            ]);

            $operation['data'] = $this->valueResolver->resolveJsonColumnData(
                $operation['data'],
                $model
            );

            $this->logger->info('Resolved json column values in data', [
                'type' => 'dbOps',
                'filters' => $operation['data'],
            ]);
        }
        // ToDoRajesh:validate
        // this should be a task: validate payload

        $validation = $this->validator->validateOperation($operation);

        if (! $validation['isValid']) {

            $this->logger->error('Operation validation failed', [
                'type' => 'dbOps',
                'errors' => $validation['errors'],
            ]);
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
            );
        }

        $this->logger->info('Operation validated successfully', [
            'type' => 'dbOps',
            'modelName' => $operation['modelName'],
        ]);
        // ToDoRajesh:validate end

        // ToDoRajesh:validate
        // this should be a task: handle
        $operation['tableName'] = $model->getConfig()->getTable();
        $operation['connection'] = $model->getConfig()->getConnection() ?? 'default';
        $this->operations[] = $operation;
        $this->logger->info('Operation added successfully', [
            'type' => 'dbOps',
            'operation' => $operation,
        ]);

        return $this;
    }

    /**
     * Add multiple operations to the collection
     *
     * @param  array[]  $operations  Array of operations to add
     *
     * @throws RuntimeException if any operation is invalid
     */
    public function addMany(array $operations): self
    {
        $this->logger->info('Adding multiple operations', [
            'type' => 'dbOps',
            'count' => count($operations),
        ]);

        foreach ($operations as $operation) {
            $this->add($operation);
        }

        $this->logger->info('All operations added successfully', [
            'type' => 'dbOps',
            'totalOperations' => count($this->operations),
        ]);

        return $this;
    }

    /**
     * Execute all operations in collection using provided database operator
     *
     * @param  DatabaseDriverInterface  $operator  Database operator to execute operations
     * @return array Operation results from database operator
     */
    public function execute(?DatabaseDriverInterface $operator = null): array
    {
        try {
            $this->logger->info('Starting execution of operations', [
                'type' => 'dbOps',
                'totalOperations' => count($this->operations),
            ]);

            $databaseDriverRegistry = $this->registryManager->getRegistry('database_driver');
            $derivedOperator = $databaseDriverRegistry->getDefaultDriver();

            if (! $operator && ! $derivedOperator) {
                $this->logger->error('No database operator available for execution', [
                    'type' => 'dbOps',
                ]);

                throw new RuntimeException('No database operator provided for execution');
            }

            // If each operation here has different connection to be used, then how should we execute?

            $dbOpResults = [];

            foreach ($this->operations as $operation) {
                $this->logger->info('Executing operation', [
                    'type' => 'dbOps',
                    'modelName' => $operation['modelName'],
                    'connection' => $operation['connection'],
                    'purpose' => $operation['purpose'] ?? '',
                    'operation' => $operation,
                ]);

                $derivedOperator = $databaseDriverRegistry->get($operation['connection']);
                $execOperator = $operator ?? $derivedOperator;

                $this->logger->info('Running operation on operator', [
                    'type' => 'dbOps',
                    'connection' => $operation['connection'],
                ]);
                $dbOpResult = $execOperator->run([$operation]);

                if (isset($operation['purpose']) && ($operation['purpose'] == 'read')) {
                    $this->logger->info(
                        'DB Query',
                        [
                            'query' => $dbOpResult[0]['raw_sql'],
                            'purpose' => $operation['purpose'] ?? '',
                            // 'result' => $dbOpResult
                        ],
                    );
                }

                $dbOpResults[] = $dbOpResult[0];
            }

            // $dbOpResults = $execOperator->run($this->operations);

            // Reset operations after execution
            $this->reset();
            $this->logger->info('Operations reset after execution', ['type' => 'dbOps']);

            // convert the stringified json to json
            foreach ($dbOpResults as $index => $dbOpResult) {
                if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result']) && ! empty($dbOpResult['result'])) {
                    $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                    $this->logger->info('Processing JSON object properties for result', [
                        'type' => 'dbOps',
                        'modelName' => $dbOpResult['operation']['modelName'],
                    ]);

                    $processedResult = $this->processJsonObjectProperties(
                        $model,
                        $dbOpResult['result']
                    );
                    // dd($dbOpResults);

                    $this->logger->info('JSON object properties processed', [
                        'type' => 'dbOps',
                        'modelName' => $dbOpResult['operation']['modelName'],
                    ]);

                    // Update the result with decoded values
                    $dbOpResults[$index]['result'] = $processedResult;
                }
            }

            foreach ($dbOpResults as $index => $dbOpResult) {
                if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result']) && ! empty($dbOpResult['result'])) {
                    $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                    if (isset($dbOpResult['operation']['expand'])) {
                        $this->logger->debug('Expanding relationships for model', [
                            'type' => 'dbOps',
                            'modelName' => $dbOpResult['operation']['modelName'],
                        ]);

                        $expander = new RelationshipExpander($model, $this, $this->registryManager);
                        $dbOpResults[$index] = $expander->expand($dbOpResult);

                        $this->logger->info('Relationships expanded for model', [
                            'type' => 'dbOps',
                            'modelName' => $dbOpResult['operation']['modelName'],
                        ]);
                    }
                }
            }

            foreach ($dbOpResults as $index => $dbOpResult) {
                if (! in_array($dbOpResult['operation']['type'], ['update', 'insert', 'delete']) && $dbOpResult['operation']['purpose'] !== 'aggregate') {
                    if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result']) && ! empty($dbOpResult['result'])) {

                        $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                        if ($model && $model->getTransformAttributes()->isNotEmpty()) {
                            $this->logger->debug('Applying result transformation for model', [
                                'type' => 'dbOps',
                                'modelName' => $dbOpResult['operation'],
                                'result' => $dbOpResult['result'],
                            ]);

                            $this->resultTransformer->setModel($model);
                            $dbOpResults[$index]['result'] = $this->resultTransformer->transform($dbOpResult['result']);

                            $this->logger->info('Result transformation applied for model', [
                                'type' => 'dbOps',
                                'modelName' => $dbOpResult['operation']['modelName'],
                            ]);
                        }
                    }
                }
            }

            $this->logger->info('Execution completed', [
                'type' => 'dbOps',
                'result' => $dbOpResults,
            ]);

            return $dbOpResults;
        } catch (\Exception $e) {
            throw $e;
            $this->logger->error('Error processing JSON/object properties', [
                'type' => 'dbOps',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset the collection
     */
    public function reset(): self
    {
        $this->logger->info('Resetting operations', [
            'type' => 'dbOps',
            'previousCount' => count($this->operations),
        ]);

        $this->operations = [];

        $this->logger->info('Operations collection has been reset', ['type' => 'dbOps']);

        return $this;
    }

    private function processJsonObjectProperties($model, array $result): array
    {
        try {
            $this->logger->info('Processing JSON/object properties', [
                'type' => 'dbOps',
                'modelName' => $model->getName(),
            ]);

            // Step 1: Get all JSON/object property names from the schema
            $objectKeys = [];
            $attributes = $model->getAttributes();
            if ($attributes) {
                foreach ($attributes as $name => $attribute) {
                    $type = $attribute->getType();
                    if (in_array($type, [AttributeType::JSON, AttributeType::JSONB, AttributeType::OBJECT])) {
                        $objectKeys[] = $name;
                        $this->logger->info('Found JSON/object property', [
                            'type' => 'dbOps',
                            'attributeName' => $name,
                            'attributeType' => $type,
                        ]);
                    }
                }
            }

            // Step 2: Decode JSON strings in the result for these keys
            $result = array_map(function ($item) use ($objectKeys) {
                foreach ($objectKeys as $key) {
                    if (isset($item[$key]) && is_string($item[$key])) {
                        $decoded = json_decode($item[$key], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $item[$key] = $decoded;
                        }
                    }
                }

                return $item;
            }, $result);

            $this->logger->info('Completed processing JSON/object properties', ['type' => 'dbOps']);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error processing JSON/object properties', [
                'type' => 'dbOps',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
