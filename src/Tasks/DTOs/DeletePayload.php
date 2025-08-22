<?php

namespace LCSEngine\Tasks\DTOs;

class DeletePayload
{
    public string $type = 'delete';

    public string $purpose = 'delete';

    public string $modelName;

    public string $deleteColumn;

    public bool $softDelete;

    public ?array $filters = null;

    public ?array $cascadePayloads = null;

    public function __construct(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function setSoftdelete(bool $value): void
    {
        $this->softDelete = $value;
    }

    public function setDeleteColumn(string $value): void
    {
        $this->deleteColumn = $value;
    }

    public function setCascadePayloads(array $cascadePayloads): void
    {
        $this->cascadePayloads = $cascadePayloads;
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);

        return array_filter($data, fn ($value) => $value !== null);
    }
}
