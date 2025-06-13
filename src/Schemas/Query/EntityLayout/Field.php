<?php

namespace LCSEngine\Schemas\Query\EntityLayout;

class Field
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'string'
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['key'] ?? '',
            $data['label'] ?? '',
            $data['type'] ?? 'string'
        );
    }
}
