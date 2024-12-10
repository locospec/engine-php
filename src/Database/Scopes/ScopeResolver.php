<?php

namespace Locospec\LCS\Database\Scopes;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Registry\RegistryManager;

class ScopeResolver
{
    private RegistryManager $registryManager;

    private string $currentModel;

    public function __construct(RegistryManager $registryManager, string $currentModel)
    {
        $this->registryManager = $registryManager;
        $this->currentModel = $currentModel;
    }

    public function resolveScopes(array|string $scopes): array
    {
        // Handle simple array of scope names
        if (is_array($scopes) && ! isset($scopes['op'])) {
            return [
                'op' => 'and',
                'conditions' => array_map(
                    fn ($scope) => $this->resolveSingleScope($scope),
                    $scopes
                ),
            ];
        }

        // Handle nested scope structure
        if (isset($scopes['op'])) {
            return [
                'op' => $scopes['op'],
                'conditions' => array_map(
                    fn ($scope) => is_array($scope) ?
                        $this->resolveScopes($scope) :
                        $this->resolveSingleScope($scope),
                    $scopes['scopes']
                ),
            ];
        }

        throw new InvalidArgumentException('Invalid scope structure');
    }

    private function resolveSingleScope(string $scopeName): array
    {
        if (str_contains($scopeName, '.')) {
            return $this->resolveRelationshipScope($scopeName);
        }

        return $this->resolveLocalScope($scopeName);
    }

    private function resolveLocalScope(string $scopeName): array
    {
        $model = $this->registryManager->get('model', $this->currentModel);
        if (! $model->hasScope($scopeName)) {
            throw new InvalidArgumentException("Scope '$scopeName' not found on model '{$this->currentModel}'");
        }

        return $model->getScope($scopeName);
    }

    private function resolveRelationshipScope(string $scopeName): array
    {
        [$relation, $scope] = explode('.', $scopeName, 2);
        $model = $this->registryManager->get('model', $this->currentModel);

        $relationship = $model->getRelationship($relation);
        if (! $relationship) {
            throw new InvalidArgumentException("Relationship '$relation' not found on model '{$this->currentModel}'");
        }

        $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
        if (! $relatedModel || ! $relatedModel->hasScope($scope)) {
            throw new InvalidArgumentException("Scope '$scope' not found on model '{$relationship->getRelatedModelName()}'");
        }

        return $this->addRelationPathToFilters(
            $relatedModel->getScope($scope),
            $relation
        );
    }

    private function addRelationPathToFilters(array $filters, string $relation): array
    {
        $addPath = function ($condition) use ($relation) {
            if (isset($condition['attribute'])) {
                $condition['attribute'] = $relation.'.'.$condition['attribute'];
            }

            return $condition;
        };

        if (isset($filters['conditions'])) {
            $filters['conditions'] = array_map($addPath, $filters['conditions']);
        }

        return $filters;
    }
}
