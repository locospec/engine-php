<?php

namespace LCSEngine\Schemas\Model\Filters;

class Filters
{
    private FilterInterface $root;

    public function __construct(FilterInterface $root)
    {
        $this->root = $root;
    }

    public function getRoot(): FilterInterface
    {
        return $this->root;
    }

    public function toArray(): array
    {
        return $this->root->toArray();
    }

    public static function condition(string $attr, ComparisonOperator $op, mixed $value): Condition
    {
        return new Condition($attr, $op, $value);
    }

    public static function group(LogicalOperator $op): FilterGroup
    {
        return new FilterGroup($op);
    }

    public static function primitive(): PrimitiveFilterSet
    {
        return new PrimitiveFilterSet();
    }

    public static function fromArray(array $data): self
    {
        // Case 3: Simple key-value pairs (convert to IS conditions in AND group)
        if (count($data) > 0 && !isset($data[0]) && !isset($data['op'])) {
            $group = new FilterGroup(LogicalOperator::AND);
            foreach ($data as $key => $value) {
                $group->add(new Condition($key, ComparisonOperator::IS, $value));
            }
            return new self($group);
        }

        // Case 2: Array of conditions (implicit AND group)
        if (isset($data[0]) && isset($data[0]['attribute'])) {
            $group = new FilterGroup(LogicalOperator::AND);
            foreach ($data as $condition) {
                $group->add(self::fromArray($condition)->getRoot());
            }
            return new self($group);
        }

        // Case 1: Full filter structure with explicit groups
        if (isset($data['op']) && isset($data['conditions'])) {
            $group = new FilterGroup(LogicalOperator::from($data['op']));
            foreach ($data['conditions'] as $condition) {
                $group->add(self::fromArray($condition)->getRoot());
            }
            return new self($group);
        }

        // Single condition
        if (isset($data['attribute']) && isset($data['op']) && isset($data['value'])) {
            $condition = new Condition(
                $data['attribute'],
                ComparisonOperator::from($data['op']),
                $data['value']
            );
            return new self($condition);
        }

        throw new \InvalidArgumentException('Invalid filter structure');
    }
} 