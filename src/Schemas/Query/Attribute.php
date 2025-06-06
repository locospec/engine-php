<?php

namespace LCS\Engine\Schemas\Query;

class Attribute
{
    public string $key;

    public string $label;

    public string $type;

    public static function fromArray(array $data): self
    {
        $attribute = new self;
        $attribute->key = $data['key'];
        $attribute->label = $data['label'];
        $attribute->type = $data['type'];

        return $attribute;
    }
}
