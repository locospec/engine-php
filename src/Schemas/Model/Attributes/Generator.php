<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Generator
{
    private string $id;

    private GeneratorType $type;

    private ?string $source = null;

    private ?string $value = null;

    private array $operations = [];

    public function __construct()
    {
        $this->id = uniqid('gen_');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setType(GeneratorType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): GeneratorType
    {
        return $this->type;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setOperations(array $operations): self
    {
        $this->validateOperations($operations);
        $this->operations = $operations;

        return $this;
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    private function validateOperations(array $operations): void
    {
        foreach ($operations as $operation) {
            if (! in_array($operation, array_column(OperationType::cases(), 'value'))) {
                throw new \InvalidArgumentException("Invalid operation type: {$operation}");
            }
        }
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'operations' => $this->operations,
        ];

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        if ($this->value !== null) {
            $data['value'] = $this->value;
        }

        return $data;
    }
}
