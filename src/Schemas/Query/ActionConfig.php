<?php

namespace LCS\Engine\Schemas\Query;

use Illuminate\Support\Collection;

class ActionConfig
{
    public string $header;

    public Collection $items;

    public function __construct(string $header)
    {
        $this->header = $header;
        $this->items = new Collection;
    }

    public function addItem(ActionItem $item): void
    {
        $this->items->put($item->key, $item);
    }

    public function removeItem(string $key): void
    {
        $this->items->forget($key);
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public static function fromArray(array $data): self
    {
        $config = new self($data['header']);

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $config->addItem(ActionItem::fromArray($item));
            }
        }

        return $config;
    }
}
