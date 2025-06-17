<?php

namespace LCSEngine\Schemas\Mutator;

class UIElement
{
    private UIElementType $type;

    private ?string $scope;

    private ?string $label;

    public function __construct(
        UIElementType $type,
        ?string $scope = null,
        ?string $label = null
    ) {
        $this->type = $type;
        $this->scope = $scope;
        $this->label = $label;
    }

    public function getType(): UIElementType
    {
        return $this->type;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            UIElementType::from($data['type']),
            $data['scope'] ?? null,
            $data['label'] ?? null
        );
    }

    public function toArray(): array
    {
        $array = [
            'type' => $this->type->value,
        ];

        if ($this->scope !== null) {
            $array['scope'] = $this->scope;
        }

        if ($this->label !== null) {
            $array['label'] = $this->label;
        }

        return $array;
    }
}
