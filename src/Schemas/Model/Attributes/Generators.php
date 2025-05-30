<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Generators extends AbstractCollection
{
    public function add(Generator $generator): void
    {
        parent::add($generator);
    }

    public function toArray(): array
    {
        return array_map(fn(Generator $generator) => $generator->toArray(), $this->items);
    }
} 