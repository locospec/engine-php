<?php

namespace LCS\Engine\Schemas\Query;

class ActionOption
{
    public string $key;
    public string $label;
    public string $url;

    public function __construct(string $key, string $label, string $url)
    {
        $this->key = $key;
        $this->label = $label;
        $this->url = $url;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['key'],
            $data['label'],
            $data['url']
        );
    }
} 