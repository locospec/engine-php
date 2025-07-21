<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Expand
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['path']);
    }
}
