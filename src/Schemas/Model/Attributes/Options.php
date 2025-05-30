<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Options extends AbstractCollection
{
    public function add(Option $option): void
    {
        parent::add($option);
    }

    public function toArray(): array
    {
        return array_map(fn(Option $option) => $option->toArray(), $this->items);
    }
} 