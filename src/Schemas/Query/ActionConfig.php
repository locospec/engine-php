<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;

class ActionConfig
{
    private string $header;
    private Collection $items;

    public function __construct(string $header, Collection $items)
    {
        $this->header = $header;
        $this->items = $items;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ActionItem $item): void
    {
        $this->items->push($item);
    }

    public function removeItem(string $key): void
    {
        $this->items = $this->items->filter(
            fn(ActionItem $item) => $item->getKey() !== $key
        );
    }

    public function toArray(): array
    {
        return [
            'header' => $this->header,
            'items' => $this->items->map(fn(ActionItem $item) => $item->toArray())->toArray()
        ];
    }

    public static function fromArray(array $data): self
    {
        $items = new Collection();
        foreach ($data['items'] as $itemData) {
            $items->push(ActionItem::fromArray($itemData));
        }

        return new self($data['header'], $items);
    }
}
