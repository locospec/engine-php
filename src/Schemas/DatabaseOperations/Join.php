<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Join
{
    private string $table;
    private string $type;
    private array $conditions;

    public function __construct(
        string $table,
        string $type = 'inner',
        array $conditions = []
    ) {
        $this->table = $table;
        $this->type = strtolower($type);
        $this->conditions = $conditions;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'type' => $this->type,
            'conditions' => $this->conditions
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['table'],
            $data['type'] ?? 'inner',
            $data['conditions'] ?? []
        );
    }
}
