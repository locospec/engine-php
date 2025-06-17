<?php

namespace LCSEngine\Schemas\Mutator;

use Illuminate\Support\Collection;

class UISchema
{
    private LayoutType $type;
    private Collection $elements;
    private Collection $options;

    public function __construct(
        LayoutType $type,
        Collection $elements,
        Collection $options = new Collection()
    ) {
        $this->type = $type;
        $this->elements = $elements;
        $this->options = $options;
    }

    public function getType(): LayoutType
    {
        return $this->type;
    }

    public function getElements(): Collection
    {
        return $this->elements;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public static function fromArray(array $data): self
    {
        $elements = collect($data['elements'] ?? [])->map(function ($element) {
            if (isset($element['elements'])) {
                // If the element has nested elements, create a new UISchema for it
                return self::fromArray($element);
            }
            return UIElement::fromArray($element);
        });

        $options = collect($data['options'] ?? []);

        return new self(
            LayoutType::from($data['type']),
            $elements,
            $options
        );
    }

    public function toArray(): array
    {
        $array = [
            'type' => $this->type->value,
            'elements' => $this->elements->map(fn($element) => $element->toArray())->all(),
        ];

        if (!$this->options->isEmpty()) {
            $array['options'] = $this->options->all();
        }

        return $array;
    }
}
