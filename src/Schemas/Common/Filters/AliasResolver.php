<?php

namespace LCSEngine\Schemas\Common\Filters;

use Illuminate\Support\Collection;

class AliasResolver
{
    private Collection $aliases;

    public function __construct(Collection $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Resolve aliases in a filter structure
     *
     * @param  Filters  $filters  The filters to resolve aliases in
     * @return Filters A new Filters instance with resolved aliases
     */
    public function resolve(Filters $filters): Filters
    {
        $root = $filters->getRoot();

        if ($root instanceof Condition) {
            return new Filters($this->resolveCondition($root));
        }

        if ($root instanceof FilterGroup) {
            return new Filters($this->resolveGroup($root));
        }

        if ($root instanceof PrimitiveFilterSet) {
            return new Filters($this->resolvePrimitiveSet($root));
        }

        return $filters;
    }

    /**
     * Resolve aliases in a condition
     */
    private function resolveCondition(Condition $condition): Condition
    {
        $attribute = $condition->getAttribute();
        if ($this->aliases->has($attribute)) {
            $alias = $this->aliases->get($attribute);
            if ($alias->hasAliasSource()) {
                return new Condition(
                    $alias->getAliasSource(),
                    $condition->getOperator(),
                    $condition->getValue()
                );
            }
        }

        return $condition;
    }

    /**
     * Resolve aliases in a filter group
     */
    private function resolveGroup(FilterGroup $group): FilterGroup
    {
        $resolvedGroup = new FilterGroup($group->getOperator());

        foreach ($group->getConditions() as $condition) {
            if ($condition instanceof Condition) {
                $resolvedGroup->add($this->resolveCondition($condition));
            } elseif ($condition instanceof FilterGroup) {
                $resolvedGroup->add($this->resolveGroup($condition));
            } elseif ($condition instanceof PrimitiveFilterSet) {
                $resolvedGroup->add($this->resolvePrimitiveSet($condition));
            }
        }

        return $resolvedGroup;
    }

    /**
     * Resolve aliases in a primitive filter set
     */
    private function resolvePrimitiveSet(PrimitiveFilterSet $set): PrimitiveFilterSet
    {
        $resolvedSet = new PrimitiveFilterSet;

        foreach ($set->getFilters() as $key => $value) {
            if ($this->aliases->has($key)) {
                $alias = $this->aliases->get($key);
                if ($alias->hasAliasSource()) {
                    $resolvedSet->add($alias->getAliasSource(), $value);

                    continue;
                }
            }
            $resolvedSet->add($key, $value);
        }

        return $resolvedSet;
    }
}
