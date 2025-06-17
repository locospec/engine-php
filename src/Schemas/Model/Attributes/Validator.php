<?php

namespace LCSEngine\Schemas\Model\Attributes;

use Illuminate\Support\Collection;

class Validator
{
    private ?string $id = null;

    private ValidatorType $type;

    private ?string $message = null;

    private Collection $operations;

    public function __construct(ValidatorType $type)
    {
        $this->type = $type;
        $this->operations = collect();
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setType(ValidatorType $type): void
    {
        $this->type = $type;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
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

    public function getType(): ValidatorType
    {
        return $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'message' => $this->message,
            'operations' => $this->operations->map(fn ($op) => $op->value)->all(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $type = ValidatorType::from($data['type'] ?? 'required');
        $validator = new self($type);
        if (isset($data['id'])) {
            $validator->setId($data['id']);
        }
        if (isset($data['message'])) {
            $validator->setMessage($data['message']);
        }
        if (isset($data['operations']) && is_array($data['operations'])) {
            $operations = collect($data['operations'])->map(fn ($op) => OperationType::from($op));
            $validator->setOperations($operations);
        }

        return $validator;
    }
}
