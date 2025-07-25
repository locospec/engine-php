<?php

namespace LCSEngine\Tasks\DTOs;

use LCSEngine\Tasks\DTOs\Interfaces\PaginatablePayloadInterface;
use LCSEngine\Tasks\DTOs\Interfaces\SortablePayloadInterface;

class ReadPayload implements PaginatablePayloadInterface, SortablePayloadInterface
{
    public string $type = 'select';

    public string $purpose = 'read';

    public string $modelName;

    public ?string $deleteColumn = null;

    public ?array $pagination = null;

    public array $sorts = [];

    public ?array $filters = null;

    public array $scopes = [];

    public array $expand = [];

    public ?array $attributes = null;

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
