<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;

class ColumnItem implements EntityLayoutItem
{
    private ?string $name;

    private Collection $items;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
        $this->items = new Collection;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addItem(EntityLayoutItem $item): void
    {
        $this->items->push($item);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function toArray(): array
    {
        $result = [];
        if ($this->name !== null) {
            $result[] = '@'.$this->name;
        }

        return array_merge($result, $this->items->map(fn ($item) => $item->toArray())->toArray());
    }
}
