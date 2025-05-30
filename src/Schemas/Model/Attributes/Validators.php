<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Validators extends AbstractCollection
{
    public function add(Validator $validator): void
    {
        parent::add($validator);
    }

    public function toArray(): array
    {
        return array_map(fn(Validator $validator) => $validator->toArray(), $this->items);
    }
} 