<?php

namespace LCSEngine\Schemas\Model\Aggregates;

class Column
{
    protected string $function;

    protected ?string $source;

    protected string $name;

    public function __construct(string $function, ?string $source, string $name)
    {
        $this->function = $function;
        $this->source = $source;
        $this->name = $name;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['function'],
            $data['source'] ?? null,
            $data['name']
        );
    }

    public function toArray(): array
    {
        return [
            'function' => $this->function,
            'source' => $this->source,
            'name' => $this->name,
        ];
    }
}
