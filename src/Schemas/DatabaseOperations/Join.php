<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Join
{
    private string $table;

    private string $type;

    private array $conditions;

    private ?array $on;

    private ?string $alias;

    private ?string $leftColType;

    private ?string $rightColType;

    public function __construct(
        string $table,
        string $type = 'inner',
        array $conditions = [],
        ?array $on = null,
        ?string $alias = null,
        ?string $leftColType = null,
        ?string $rightColType = null
    ) {
        $this->table = $table;
        $this->type = strtolower($type);
        $this->conditions = $conditions;
        $this->on = $on;
        $this->alias = $alias;
        $this->leftColType = $leftColType;
        $this->rightColType = $rightColType;
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

    public function getOn(): ?array
    {
        return $this->on;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getLeftColType(): ?string
    {
        return $this->leftColType;
    }

    public function getRightColType(): ?string
    {
        return $this->rightColType;
    }

    public function toArray(): array
    {
        $result = [
            'table' => $this->table,
            'type' => $this->type,
        ];

        // Include conditions for backward compatibility
        if (! empty($this->conditions)) {
            $result['conditions'] = $this->conditions;
        }

        // Include new format properties
        if ($this->on !== null) {
            $result['on'] = $this->on;
        }

        if ($this->alias !== null) {
            $result['alias'] = $this->alias;
        }

        if ($this->leftColType !== null) {
            $result['left_col_type'] = $this->leftColType;
        }

        if ($this->rightColType !== null) {
            $result['right_col_type'] = $this->rightColType;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['table'],
            $data['type'] ?? 'inner',
            $data['conditions'] ?? [],
            $data['on'] ?? null,
            $data['alias'] ?? null,
            $data['left_col_type'] ?? null,
            $data['right_col_type'] ?? null
        );
    }
}
