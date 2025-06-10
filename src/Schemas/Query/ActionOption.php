<?php

namespace LCSEngine\Schemas\Query;

class ActionOption
{
    private string $key;

    private string $label;

    private string $url;

    public function __construct(string $key, string $label, string $url)
    {
        $this->key = $key;
        $this->label = $label;
        $this->url = $url;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'url' => $this->url,
        ];
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
