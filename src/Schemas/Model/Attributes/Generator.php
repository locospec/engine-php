<?php

namespace LCSEngine\Schemas\Model\Attributes;

use Illuminate\Support\Collection;

class Generator
{
    private ?string $id = null;

    private GeneratorType $type;

    private ?Collection $def = null;

    private ?string $source = null;

    private ?string $value = null;

    private Collection $operations;

    public function __construct(GeneratorType $type)
    {
        $this->type = $type;
        $this->operations = collect();
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setType(GeneratorType $type): void
    {
        $this->type = $type;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function setOperations(Collection $operations): void
    {
        $this->operations = $operations;
    }

    public function addOperation(OperationType $operation): void
    {
        $this->operations->push($operation);
    }

    public function removeOperation(OperationType $operation): void
    {
        $this->operations = $this->operations->reject(fn ($op) => $op === $operation)->values();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): GeneratorType
    {
        return $this->type;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function setDef(?array $def): void
    {
        if ($def) {
            if (! isset($def['StartAt']) || ! isset($def['States'])) {
                throw new \InvalidArgumentException('State machine definition must contain "StartAt" and "States" keys');
            }
            $this->def = collect($def);
        } else {
            $this->def = null;
        }
    }

    public function getDef(): ?Collection
    {
        return $this->def;
    }

    public function toArray(): array
    {
        $arr = [
            'id' => $this->id,
            'type' => $this->type->value,
            'source' => $this->source,
            'value' => $this->value,
            'operations' => $this->operations->map(fn ($op) => $op->value)->all(),
        ];

        if ($this->def) {
            $arr['def'] = $this->def->all();
        }

        return $arr;
    }

    public static function fromArray(array $data): self
    {
        $type = GeneratorType::from($data['type'] ?? 'uuid');
        $generator = new self($type);
        if (isset($data['id'])) {
            $generator->setId($data['id']);
        }
        if (isset($data['source'])) {
            $generator->setSource($data['source']);
        }
        if (isset($data['value'])) {
            $generator->setValue($data['value']);
        }
        if (isset($data['operations']) && is_array($data['operations'])) {
            $operations = collect($data['operations'])->map(fn ($op) => OperationType::from($op));
            $generator->setOperations($operations);
        }
        if (isset($data['def'])) {
            $generator->setDef($data['def']);
        }

        return $generator;
    }
}
