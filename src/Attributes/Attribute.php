<?php

namespace Locospec\Engine\Attributes;

class Attribute implements AttributeInterface
{
    private string $name;

    private string $type;

    private string $label;

    public function __construct(string $name, string $type, string $label)
    {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
    }

    /**
     * Create an Attribute instance from an object.
     *
     * @param  string  $name  The attribute name (e.g., "uuid").
     * @param  object  $data  The object containing attribute details (e.g., type, label).
     */
    public static function fromObject(string $name, object $data): self
    {
        $type = $data->type ?? 'string';
        $label = $data->label ?? $name;

        return new self($name, $type, $label);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
        ];
    }

    public function toObject(): object
    {
        return (object) $this->toArray();
    }
}
