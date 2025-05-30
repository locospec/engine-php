<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Validator
{
    private string $id;
    private ValidatorType $type;
    private string $message;
    private array $operations = [];

    public function __construct()
    {
        $this->id = uniqid('val_');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setType(ValidatorType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ValidatorType
    {
        return $this->type;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
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
            if (!in_array($operation, array_column(OperationType::cases(), 'value'))) {
                throw new \InvalidArgumentException("Invalid operation type: {$operation}");
            }
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'message' => $this->message,
            'operations' => $this->operations,
        ];
    }
} 