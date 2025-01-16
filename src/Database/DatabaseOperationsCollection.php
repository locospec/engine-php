<?php

namespace Locospec\Engine\Database;

use Locospec\Engine\Database\Filters\FilterGroup;
use Locospec\Engine\Database\Relationships\RelationshipExpander;
use Locospec\Engine\Database\Relationships\RelationshipResolver;
use Locospec\Engine\Database\Scopes\ScopeResolver;
use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Registry\DatabaseDriverInterface;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Specifications\SpecificationValidator;
use RuntimeException;

class DatabaseOperationsCollection
{
    /** @var array[] */
    private array $operations = [];

    private SpecificationValidator $validator;

    private ValueResolver $valueResolver;

    private ?RegistryManager $registryManager = null;

    private ?QueryContext $context = null;

    public function __construct()
    {
        $this->validator = new SpecificationValidator;
        $this->valueResolver = new ValueResolver;
    }

    public function setContext(QueryContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function setRegistryManager(RegistryManager $registryManager): self
    {
        $this->registryManager = $registryManager;

        return $this;
    }

    private function mergeFilters(array $scopeFilters, array $existingFilters): array
    {
        // If either filter is empty, return the other
        if (empty($scopeFilters)) {
            return $existingFilters;
        }
        if (empty($existingFilters)) {
            return $scopeFilters;
        }

        // If both filters have 'and' operator, merge their conditions
        if (($scopeFilters['op'] ?? 'and') === 'and' &&
            ($existingFilters['op'] ?? 'and') === 'and'
        ) {
            return [
                'op' => 'and',
                'conditions' => array_merge(
                    $scopeFilters['conditions'] ?? [],
                    $existingFilters['conditions'] ?? []
                ),
            ];
        }

        // Otherwise wrap them in an AND
        return [
            'op' => 'and',
            'conditions' => [
                $scopeFilters,
                $existingFilters,
            ],
        ];
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
        if (! isset($operation['modelName'])) {
            throw new InvalidArgumentException('Operation must specify a modelName');
        }

        $model = $this->registryManager->get('model', $operation['modelName']);
        if (! $model) {
            throw new InvalidArgumentException("Model not found: {$operation['modelName']}");
        }

        if (isset($operation['scopes'])) {
            $resolver = new ScopeResolver($this->registryManager, $operation['modelName']);
            $scopeFilters = $resolver->resolveScopes($operation['scopes']);

            // Merge filters more efficiently
            if (isset($operation['filters'])) {
                $operation['filters'] = $this->mergeFilters($scopeFilters, $operation['filters']);
            } else {
                $operation['filters'] = $scopeFilters;
            }

            unset($operation['scopes']);
        }

        // dd($operation);

        if (isset($operation['filters'])) {
            $operation = FilterGroup::normalize($operation);
            $resolver = new RelationshipResolver($model, $this, $this->registryManager);
            $operation = $resolver->resolveFilters($operation);
        }

        // 4. Resolve any context variables in filter values
        if (isset($operation['filters']) && $this->context) {
            $operation['filters'] = $this->valueResolver->resolveFilters(
                $operation['filters'],
                $this->context
            );
        }

        $validation = $this->validator->validateOperation($operation);

        if (! $validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
            );
        }

        $operation['tableName'] = $model->getConfig()->getTable();
        $operation['connection'] = $model->getConfig()->getConnection() ?? 'default';
        $this->operations[] = $operation;

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
        foreach ($operations as $operation) {
            $this->add($operation);
        }

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
        $databaseDriverRegistry = $this->registryManager->getRegistry('database_driver');
        $derivedOperator = $databaseDriverRegistry->getDefaultDriver();

        if (! $operator && ! $derivedOperator) {
            throw new RuntimeException('No database operator provided for execution');
        }

        // If each operation here has different connection to be used, then how should we execute?

        $dbOpResults = [];

        foreach ($this->operations as $operation) {
            $derivedOperator = $databaseDriverRegistry->get($operation['connection']);
            $execOperator = $operator ?? $derivedOperator;
            $dbOpResult = $execOperator->run([$operation]);
            $dbOpResults[] = $dbOpResult[0];
        }

        // $dbOpResults = $execOperator->run($this->operations);

        // Reset operations after execution
        $this->reset();

        foreach ($dbOpResults as $index => $dbOpResult) {
            if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result'])) {
                $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                if (isset($dbOpResult['operation']['expand'])) {
                    $expander = new RelationshipExpander($model, $this, $this->registryManager);
                    $dbOpResults[$index] = $expander->expand($dbOpResult);
                }
            }
        }

        foreach ($dbOpResults as $index => $dbOpResult) {

            if (isset($dbOpResult['operation']['modelName']) && isset($dbOpResult['result'])) {

                $model = $this->registryManager->get('model', $dbOpResult['operation']['modelName']);

                if ($model && $model->getAliases()) {
                    $aliasTransformer = new AliasTransformation($model);
                    $dbOpResults[$index]['result'] = $aliasTransformer->transform($dbOpResult['result']);
                }
            }
        }

        return $dbOpResults;
    }

    /**
     * Reset the collection
     */
    public function reset(): self
    {
        $this->operations = [];

        return $this;
    }
}
