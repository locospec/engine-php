<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Sort
{
    private string $field;
    private string $direction;

    public function __construct(string $field, string $direction = 'asc')
    {
        $this->field = $field;
        $this->direction = strtolower($direction);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['field'],
            $data['direction'] ?? 'asc'
        );
    }
}
