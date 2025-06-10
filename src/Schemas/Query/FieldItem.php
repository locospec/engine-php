<?php

namespace LCSEngine\Schemas\Query;

class FieldItem implements EntityLayoutItem
{
    private string $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function toArray(): array
    {
        return [$this->field];
    }
}
