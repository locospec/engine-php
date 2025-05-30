<?php

namespace LCSEngine\Schemas\Model\Attributes;

abstract class AbstractCollection
{
    protected array $items = [];

    public function add($item): void
    {
        $this->items[] = $item;
    }

    public function remove(string $id): void
    {
        foreach ($this->items as $key => $item) {
            if ($item->getId() === $id) {
                unset($this->items[$key]);
                break;
            }
        }
    }

    public function getAll(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
} 