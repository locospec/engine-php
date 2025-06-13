<?php

namespace LCSEngine\Schemas\Query\EntityLayout;

use Illuminate\Support\Collection;

class Section
{
    protected string $label;
    protected Collection $fields; // of Field|Section

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->fields = collect();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function addField(Field $field): self
    {
        $this->fields->push($field);
        return $this;
    }

    public function addSection(Section $section): self
    {
        $this->fields->push($section);
        return $this;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function toArray(): array
    {
        return [
            'section' => $this->label,
            'fields' => $this->fields->map(function ($item) {
                return $item instanceof Section
                    ? $item->toArray()
                    : $item->toArray();
            })->all()
        ];
    }

    public static function fromArray(array $data): self
    {
        $section = new self($data['section'] ?? '');
        foreach ($data['fields'] ?? [] as $item) {
            if (isset($item['key'])) {
                $section->addField(Field::fromArray($item));
            } else {
                $section->addSection(self::fromArray($item));
            }
        }
        return $section;
    }
}
