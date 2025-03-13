<?php

namespace Locospec\Engine\Attributes;

class Attribute implements AttributeInterface
{
    private string $name;

    private string $type;

    private string $label;

    private array $options;
    
    private array $generations;

    private array $validations;

    public function __construct(string $name, string $type, string $label, array $generations, array $validations,  array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
        $this->options = $options;
        $this->generations = $generations;
        $this->validations = $validations;
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
        $options = $data->options ?? [];
        $generations = $data->generations ?? [];
        $validations = $data->validations ?? [];

        return new self($name, $type, $label, $generations, $validations, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGenerations(): array
    {
        return $this->generations;
    }
   
    public function getValidations(): array
    {
        return $this->validations;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'label' => $this->label,
        ];

        if (! empty($this->options)) {
            $data['options'] = $this->options;
        }

        if (! empty($this->generations)) {
            $data['generations'] = $this->generations;
        }

        if (! empty($this->validations)) {
            $data['validations'] = $this->validations;
        }

        return $data;
    }

    public function toObject(): object
    {
        return (object) $this->toArray();
    }
}
