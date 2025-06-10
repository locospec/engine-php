<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;

class SectionItem implements EntityLayoutItem
{
    private string $header;
    private Collection $items;

    public function __construct(string $header)
    {
        $this->header = $header;
        $this->items = new Collection();
    }

    public function getHeader(): string
    {
        return $this->header;
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
        $data = ['$' . $this->header];
        return array_merge(
            $data,
            $this->items->map(fn(EntityLayoutItem $item) => $item->toArray())->flatten()->toArray()
        );
    }
}
