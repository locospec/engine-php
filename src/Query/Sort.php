<?php

namespace Locospec\LCS\Query;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class Sort
{
    private string $attribute;
    private string $direction;
    private AttributePath $path;

    private const VALID_DIRECTIONS = ['asc', 'desc'];

    public function __construct(string $attribute, string $direction = 'asc')
    {
        $this->attribute = $attribute;
        $this->direction = strtolower($direction);
        $this->path = AttributePath::parse($attribute);

        $this->validate();
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getAttributePath(): AttributePath
    {
        return $this->path;
    }

    public function validate(): void
    {
        if (empty($this->attribute)) {
            throw new InvalidArgumentException("Sort must specify an attribute");
        }

        if (!in_array($this->direction, self::VALID_DIRECTIONS)) {
            throw new InvalidArgumentException(
                "Invalid sort direction. Valid directions are: " . implode(', ', self::VALID_DIRECTIONS)
            );
        }
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['attribute'])) {
            throw new InvalidArgumentException("Sort must specify an attribute");
        }

        return new self(
            $data['attribute'],
            $data['direction'] ?? 'asc'
        );
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'direction' => $this->direction,
        ];
    }
}
