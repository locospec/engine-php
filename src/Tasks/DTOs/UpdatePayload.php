<?php

namespace LCSEngine\Tasks\DTOs;

class UpdatePayload
{
    public string $type = 'update';

    public string $purpose = 'update';

    public string $modelName;

    public ?array $filters = null;

    public array $data;

    public function __construct(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function setData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);

        return array_filter($data, fn ($value) => $value !== null);
    }
}
