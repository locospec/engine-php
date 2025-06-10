<?php

namespace LCSEngine\Schemas\Model;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;

class ScopeResolver
{
    private RegistryManager $registryManager;

    private string $currentModelName;

    private ?string $currentViewName;

    public function __construct(RegistryManager $registryManager, string $currentModelName, ?string $currentViewName)
    {
        $this->registryManager = $registryManager;
        $this->currentModelName = $currentModelName;
        $this->currentViewName = $currentViewName;
    }

    public function resolveScopes(array|string $scopes): array
    {
        // Handle simple array of scope names
        if (is_array($scopes) && ! isset($scopes['op'])) {
            if (count($scopes) === 1) {
                return $this->resolveSingleScope($scopes[0]);
            }

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
                    function ($scope) {
                        if (is_array($scope) && isset($scope['op'])) {
                            return $this->resolveScopes($scope);
                        }
                        $resolved = $this->resolveSingleScope($scope);

                        return $resolved['conditions'][0];
                    },
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
        $model = $this->registryManager->get('model', $this->currentModelName);

        if (! $model->getScopes()->has($scopeName)) {
            if (isset($this->currentViewName)) {
                $view = $this->registryManager->get('view', $this->currentViewName);
                if (! $view->hasScope($scopeName)) {
                    throw new InvalidArgumentException("Scope '$scopeName' not found on model or view '{$this->currentModelName}', '{$this->currentViewName}'");
                }

                return $view->getScope($scopeName);
            } else {
                throw new InvalidArgumentException("Scope '$scopeName' not found on model '{$this->currentModelName}'");
            }
        }

        return $model->getScope($scopeName)->toArray();
    }

    private function resolveRelationshipScope(string $scopeName): array
    {
        [$relation, $scope] = explode('.', $scopeName, 2);
        $model = $this->registryManager->get('model', $this->currentModelName);

        $relationship = $model->getRelationship($relation);
        if (! $relationship) {
            throw new InvalidArgumentException("Relationship '$relation' not found on model '{$this->currentModelName}'");
        }

        $relatedModel = $this->registryManager->get('model', $relationship->getRelatedModelName());
        if (! $relatedModel->hasScope($scope)) {
            throw new InvalidArgumentException("Scope '$scope' not found on model '{$relationship->getRelatedModelName()}'");
        }

        $scopeFilter = $relatedModel->getScope($scope);

        return $this->addRelationPathToFilters($scopeFilter, $relation);
    }

    private function addRelationPathToFilters(array $filters, string $relation): array
    {
        if (isset($filters['conditions'])) {
            foreach ($filters['conditions'] as &$condition) {
                if (isset($condition['attribute'])) {
                    $condition['attribute'] = $relation.'.'.$condition['attribute'];
                }
            }
        }

        return $filters;
    }
}
