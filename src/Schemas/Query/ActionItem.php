<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;

class ActionItem
{
    private string $key;
    private string $label;
    private string $url;
    private string $icon;
    private bool $confirmation;
    private Collection $options;

    public function __construct(
        string $key,
        string $label,
        string $url = '',
        string $icon = '',
        bool $confirmation = false
    ) {
        $this->key = $key;
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->confirmation = $confirmation;
        $this->options = new Collection();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getConfirmation(): bool
    {
        return $this->confirmation;
    }

    public function addOption(ActionOption $option): void
    {
        $this->options->push($option);
    }

    public function removeOption(string $key): void
    {
        $this->options = $this->options->filter(
            fn(ActionOption $option) => $option->getKey() !== $key
        );
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'label' => $this->label,
        ];

        if ($this->url !== '') {
            $data['url'] = $this->url;
        }

        if ($this->icon !== '') {
            $data['icon'] = $this->icon;
        }

        if ($this->confirmation) {
            $data['confirmation'] = true;
        }

        if ($this->options->isNotEmpty()) {
            $data['options'] = $this->options->map(fn(ActionOption $option) => $option->toArray())->toArray();
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $item = new self(
            $data['key'],
            $data['label'],
            $data['url'] ?? '',
            $data['icon'] ?? '',
            $data['confirmation'] ?? false
        );

        if (isset($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $item->addOption(ActionOption::fromArray($optionData));
            }
        }

        return $item;
    }
}
