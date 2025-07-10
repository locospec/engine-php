<?php

namespace LCSEngine\Tasks\DTOs;

class ReadPayload
{
    public string $type = 'select';
    public string $modelName;
    public ?string $deleteColumn = null;
    public ?array $pagination = null;
    public array $sorts = [];
    public ?array $filters = null;
    public array $scopes = [];
    public array $expand = [];

    public function __construct(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);
        return array_filter($data, fn ($value) => $value !== null);
    }
}
