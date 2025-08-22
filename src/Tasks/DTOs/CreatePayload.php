<?php

namespace LCSEngine\Tasks\DTOs;

class CreatePayload
{
    public string $type = 'insert';

    public string $purpose = 'create';

    public string $modelName;

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
