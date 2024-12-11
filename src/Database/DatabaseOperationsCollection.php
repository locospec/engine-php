<?php

namespace Locospec\LCS\Database;

use Locospec\LCS\Database\Filters\FilterGroup;
use Locospec\LCS\Database\Relationships\RelationshipResolver;
use Locospec\LCS\Database\Scopes\ScopeResolver;
use Locospec\LCS\Database\Validators\DatabaseOperationsValidator;
use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Registry\DatabaseDriverInterface;
use Locospec\LCS\Registry\RegistryManager;
use RuntimeException;

class DatabaseOperationsCollection
{
    /** @var array[] */
    private array $operations = [];

    private DatabaseOperationsValidator $validator;

    private ValueResolver $valueResolver;

    private ?RegistryManager $registryManager = null;

    private ?string $currentModel = null;

    private ?DatabaseDriverInterface $operator = null;

    private ?QueryContext $context = null;

    public function __construct(?DatabaseDriverInterface $operator = null)
    {
        $this->validator = new DatabaseOperationsValidator;
        $this->operator = $operator;
        $this->valueResolver = new ValueResolver;
    }

    public function setContext(QueryContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function setOperator(DatabaseDriverInterface $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function setRegistryManager(RegistryManager $registryManager): self
    {
        $this->registryManager = $registryManager;

        return $this;
    }

    public function setCurrentModel(string $modelName): self
    {
        $this->currentModel = $modelName;

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
        // Convert shorthand filters to full-form structure if present
        // if (isset($operation['filters']) && is_array($operation['filters'])) {
        //     $operation = $this->convertShorthandFilters($operation);
        // }

        // if (isset($operation['filters'])) {
        //     $operation = $this->convertShorthandFilters($operation);
        // }

        if (isset($operation['scopes'])) {
            if (! $this->registryManager || ! $this->currentModel) {
                throw new InvalidArgumentException('RegistryManager and current model are required for scope resolution');
            }

            $resolver = new ScopeResolver($this->registryManager, $this->currentModel);
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

            if ($this->registryManager && $this->currentModel) {
                $model = $this->registryManager->get('model', $this->currentModel);
                $resolver = new RelationshipResolver($model, $this, $this->registryManager);
                $operation = $resolver->resolveFilters($operation);
            }
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
        if (! $operator && ! $this->operator) {
            throw new RuntimeException('No database operator provided for execution');
        }

        $execOperator = $operator ?? $this->operator;
        $results = $execOperator->run($this->operations);

        if ($this->currentModel && isset($results['result'])) {
            $model = $this->registryManager->get('model', $this->currentModel);
            if ($model && $model->getAliases()) {
                $aliasTransformer = new AliasTransformation($model);
                $results['result'] = $aliasTransformer->transform($results['result']);
            }
        }

        // Reset operations after execution
        $this->reset();

        return $results;
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
