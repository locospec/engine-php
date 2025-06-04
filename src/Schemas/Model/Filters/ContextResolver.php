<?php

namespace LCSEngine\Schemas\Model\Filters;

class ContextResolver
{
    private array $context;

    public function __construct(array $context)
    {
        $this->context = $context;
    }

    /**
     * Resolve context variables in a filter structure
     *
     * @param  Filters  $filters  The filters to resolve context in
     * @return Filters A new Filters instance with resolved context values
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
     * Resolve context variables in a condition value
     */
    private function resolveCondition(Condition $condition): Condition
    {
        $value = $condition->getValue();
        $resolvedValue = $this->resolveValue($value);

        if ($resolvedValue !== $value) {
            return new Condition(
                $condition->getAttribute(),
                $condition->getOperator(),
                $resolvedValue
            );
        }

        return $condition;
    }

    /**
     * Resolve context variables in a filter group
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
     * Resolve context variables in a primitive filter set
     */
    private function resolvePrimitiveSet(PrimitiveFilterSet $set): PrimitiveFilterSet
    {
        $resolvedSet = new PrimitiveFilterSet;

        foreach ($set->getFilters() as $key => $value) {
            $resolvedValue = $this->resolveValue($value);
            $resolvedSet->add($key, $resolvedValue);
        }

        return $resolvedSet;
    }

    /**
     * Resolve a value that may contain context variables
     */
    private function resolveValue(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, '$.') && strlen($value) > 2) {
            $key = substr($value, 2);

            return $this->context[$key] ?? null;
        }

        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $k => $v) {
                $resolved[$k] = $this->resolveValue($v);
            }

            return $resolved;
        }

        return $value;
    }
}
