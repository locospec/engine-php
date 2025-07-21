<?php

namespace LCSEngine\Schemas\DatabaseOperations;

class Scope
{
    private string $name;
    private array $parameters;

    public function __construct(string $name, array $parameters = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'parameters' => $this->parameters
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['parameters'] ?? []
        );
    }
}
