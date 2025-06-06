<?php

namespace LCS\Engine\Schemas\Query;

class Filter
{
    public string $key;

    public string $value;

    public static function fromArray(array $data): self
    {
        $filter = new self;
        $filter->key = $data['key'];
        $filter->value = $data['value'];

        return $filter;
    }
}
