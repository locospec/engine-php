<?php

namespace Locospec\LCS\Views;

class ViewDefinition
{
    private string $name;
    private string $centralModel;
    private array $modelViewAttributes = [];
    private $additionalAttributes = [];

    public function __construct(string $name, string $centralModel, array $modelViewAttributes, $additionalAttributes)
    {
        $this->name = $name;
        $this->centralModel = $centralModel;
        $this->modelViewAttributes = $modelViewAttributes;
        $this->additionalAttributes = $additionalAttributes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCentralModel(): string
    {
        return $this->centralModel;
    }

    public function getCentralModelAttributes(): array
    {
        return $this->modelViewAttributes;
    }

    public function getAdditionalAttributes()
    {
        return $this->additionalAttributes;
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['centralModel'], $data['modelViewAttributes'], $data['additionalAttributes']);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => 'view',
            'centralModel' => $this->centralModel,
            'modelViewAttributes' => $this->modelViewAttributes,
            'additionalAttributes' => $this->additionalAttributes,
        ];
    }
}
