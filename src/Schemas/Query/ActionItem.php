<?php

namespace LCS\Engine\Schemas\Query;

use Illuminate\Support\Collection;

class ActionItem
{
    public string $key;
    public string $label;
    public string $url;
    public string $icon;
    public Collection $options;
    public bool $confirmation;

    public function __construct(string $key, string $label, string $url, string $icon, bool $confirmation = false)
    {
        $this->key = $key;
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->confirmation = $confirmation;
        $this->options = new Collection();
    }

    public function addOption(ActionOption $opt): void
    {
        $this->options->put($opt->key, $opt);
    }

    public function removeOption(string $key): void
    {
        $this->options->forget($key);
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public static function fromArray(array $data): self
    {
        $item = new self(
            $data['key'],
            $data['label'],
            $data['url'],
            $data['icon'],
            $data['confirmation'] ?? false
        );
        
        if (isset($data['options'])) {
            foreach ($data['options'] as $option) {
                $item->addOption(ActionOption::fromArray($option));
            }
        }
        
        return $item;
    }
} 