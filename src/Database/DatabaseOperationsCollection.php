<?php

namespace LCSEngine\Database;

use LCSEngine\Database\Filters\FilterGroup;
use LCSEngine\Database\Relationships\RelationshipExpander;
use LCSEngine\Database\Relationships\RelationshipResolver;
use LCSEngine\Database\Scopes\ScopeResolver;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\LCS;
use LCSEngine\Registry\DatabaseDriverInterface;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\SpecValidator;
use RuntimeException;

class DatabaseOperationsCollection
{
    /** @var array[] */
    private array $operations = [];

    private SpecValidator $validator;

    private ValueResolver $valueResolver;

    private AliasTransformation $aliasTransformer;

    private ?RegistryManager $registryManager = null;

    private ?QueryContext $context = null;

    public function __construct()
    {
        $this->validator = new SpecValidator;
        $this->valueResolver = new ValueResolver;
        $this->aliasTransformer = new AliasTransformation;
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

    private function mergeFilters(array $scopeFilters, array $existingFilters): array
    {
        $this->logger->info('Merging filters', [
            'type' => 'dbOps',
            'scopeFilters' => $scopeFilters,
            'existingFilters' => $existingFilters,
        ]);
        // If either filter is empty, return the other
        if (empty($scopeFilters)) {
            $this->logger->info('Scope filters empty, returning existing filters', [
                'type' => 'dbOps',
                'existingFilters' => $existingFilters,
            ]);

            return $existingFilters;
        }
        if (empty($existingFilters)) {
            $this->logger->info('Existing filters empty, returning scope filters', [
                'type' => 'dbOps',
                'scopeFilters' => $scopeFilters,
            ]);

            return $scopeFilters;
        }

        // If both filters have 'and' operator, merge their conditions
        if (($scopeFilters['op'] ?? 'and') === 'and' &&
            ($existingFilters['op'] ?? 'and') === 'and'
        ) {
            $merged = [
                'op' => 'and',
                'conditions' => array_merge(
                    $scopeFilters['conditions'] ?? [],
                    $existingFilters['conditions'] ?? []
                ),
            ];
            $this->logger->info('Both filters use AND operator, merged conditions', [
                'type' => 'dbOps',
                'merged' => $merged,
            ]);

            return $merged;
        }

        // Otherwise wrap them in an AND
        $merged = [
            'op' => 'and',
            'conditions' => [
                $scopeFilters,
                $existingFilters,
            ],
        ];

        $this->logger->info('Filters have different operators, wrapping them in AND', [
            'type' => 'dbOps',
            'merged' => $merged,
        ]);

        return $merged;
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
            $viewName = isset($operation['viewName']) ? $operation['viewName'] : null;

            $resolver = new ScopeResolver($this->registryManager, $operation['modelName'], $viewName);
            $scopeFilters = $resolver->resolveScopes($operation['scopes']);

            $this->logger->info('Scopes resolved to filters', [
                'type' => 'dbOps',
                'scopeFilters' => $scopeFilters,
            ]);

            // Merge filters more efficiently
            if (isset($operation['filters'])) {
                $operation['filters'] = $this->mergeFilters($scopeFilters, $operation['filters']);
                $this->logger->info('Merged scope filters with existing filters', [
                    'type' => 'dbOps',
                    'mergedFilters' => $operation['filters'],
                ]);
            } else {
                $operation['filters'] = $scopeFilters;
                $this->logger->info('No existing filters, using scope filters', [
                    'type' => 'dbOps',
                    'filters' => $operation['filters'],
                ]);
            }

            unset($operation['scopes']);
        }

        if (isset($operation['filters'])) {
            $this->logger->info('Normalizing filters', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);

            $operation = FilterGroup::normalize($operation, $model);
            $this->logger->info('Filters normalized', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);

            $resolver = new RelationshipResolver($model, $this, $this->registryManager);
            $operation = $resolver->resolveFilters($operation);
            $this->logger->info('Relationship filters resolved', [
                'type' => 'dbOps',
                'operation' => $operation,
            ]);
        }

        // 4. Resolve any context variables in filter values
        if (isset($operation['filters']) && $this->context) {
            $this->logger->info('Resolving context in filters', [
                'type' => 'dbOps',
                'filters' => $operation['filters'],
            ]);
            $operation['filters'] = $this->valueResolver->resolveFilters(
                $operation['filters'],
                $this->context
            );
            $this->logger->info('Context resolved in filters', [
                'type' => 'dbOps',
                'filters' => $operation['filters'],
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
                ]);

                $derivedOperator = $databaseDriverRegistry->get($operation['connection']);
                $execOperator = $operator ?? $derivedOperator;

                $this->logger->info('Running operation on operator', [
                    'type' => 'dbOps',
                    'connection' => $operation['connection'],
                ]);
                $dbOpResult = $execOperator->run([$operation]);

                $this->logger->info('Operation executed', [
                    'type' => 'dbOps',
                    // 'result' => $dbOpResult,
                ]);

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
                        $this->logger->info('Expanding relationships for model', [
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
                if (! in_array($dbOpResult['operation']['type'], ['update', 'insert', 'delete'])) {
                    if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result']) && ! empty($dbOpResult['result'])) {

                        $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                        if ($model && $model->getAliases()) {
                            $this->logger->info('Applying alias transformation for model', [
                                'type' => 'dbOps',
                                'modelName' => $dbOpResult['operation']['modelName'],
                            ]);

                            $this->aliasTransformer->setModel($model);
                            $dbOpResults[$index]['result'] = $this->aliasTransformer->transform($dbOpResult['result']);

                            $this->logger->info('Alias transformation applied for model', [
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
                foreach ($attributes->getAttributes() as $name => $attribute) {
                    $type = $attribute->getType();
                    if ($type === 'json' || $type === 'object') {
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
