<?php

namespace LCSEngine\Schemas\Common\Filters;

class FilterCleaner
{
    public function clean(Filters $filters): Filters
    {
        $root = $filters->getRoot();

        if ($root instanceof Condition) {
            return $this->cleanCondition($root);
        }

        if ($root instanceof FilterGroup) {
            return $this->cleanGroup($root);
        }

        if ($root instanceof PrimitiveFilterSet) {
            return $this->cleanPrimitiveSet($root);
        }

        return new Filters(new FilterGroup(LogicalOperator::AND));
    }

    private function cleanCondition(Condition $condition): Filters
    {
        if ($this->isEmptyValue($condition->getValue())) {
            return new Filters(new FilterGroup(LogicalOperator::AND));
        }

        return new Filters($condition);
    }

    private function cleanGroup(FilterGroup $group): Filters
    {
        $cleanGroup = new FilterGroup($group->getOperator());

        foreach ($group->getConditions() as $condition) {
            if ($condition instanceof Condition) {
                if (! $this->isEmptyValue($condition->getValue())) {
                    $cleanGroup->add($condition);
                }
            } elseif ($condition instanceof FilterGroup) {
                $cleanedSubGroup = $this->cleanGroup($condition);
                $subRoot = $cleanedSubGroup->getRoot();
                if ($subRoot instanceof FilterGroup && ! empty($subRoot->getConditions())) {
                    $cleanGroup->add($subRoot);
                }
            }
        }

        return new Filters($cleanGroup);
    }

    private function cleanPrimitiveSet(PrimitiveFilterSet $set): Filters
    {
        return new Filters($set);
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || (is_array($value) && empty($value)) || $value === '~delete~';
    }
}
