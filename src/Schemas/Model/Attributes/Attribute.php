<?php

namespace LCSEngine\Schemas\Model\Attributes;

class Attribute
{
    private string $name;
    private string $label;
    private AttributeType $type;
    private ?Generators $generators = null;
    private ?Validators $validators = null;
    private ?Options $options = null;
    private bool $primaryKey = false;
    private bool $deleteKey = false;
    private bool $labelKey = false;
    private ?string $source = null;
    private ?string $transform = null;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setType(AttributeType $type): void
    {
        $this->type = $type;
    }

    public function getType(): AttributeType
    {
        return $this->type;
    }

    public function setPrimaryKey(bool $flag): void
    {
        $this->primaryKey = $flag;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function setLabelKey(bool $flag): void
    {
        $this->labelKey = $flag;
    }

    public function isLabelKey(): bool
    {
        return $this->labelKey;
    }

    public function setDeleteKey(bool $flag): void
    {
        $this->deleteKey = $flag;
    }

    public function isDeleteKey(): bool
    {
        return $this->deleteKey;
    }

    public function setAliasSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getAliasSource(): ?string
    {
        return $this->source;
    }

    public function setAliasTransformation(?string $transform): void
    {
        $this->transform = $transform;
    }

    public function getAliasTransformation(): ?string
    {
        return $this->transform;
    }

    public function addGenerator(Generator $generator): void
    {
        if ($this->generators === null) {
            $this->generators = new Generators();
        }
        $this->generators->add($generator);
    }

    public function addValidator(Validator $validator): void
    {
        if ($this->validators === null) {
            $this->validators = new Validators();
        }
        $this->validators->add($validator);
    }

    public function addOption(Option $option): void
    {
        if ($this->options === null) {
            $this->options = new Options();
        }
        $this->options->add($option);
    }

    public function getGenerators(): ?Generators
    {
        return $this->generators;
    }

    public function getValidators(): ?Validators
    {
        return $this->validators;
    }

    public function getOptions(): ?Options
    {
        return $this->options;
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'label' => $this->label,
        ];

        if ($this->primaryKey) {
            $data['primaryKey'] = true;
        }

        if ($this->deleteKey) {
            $data['deleteKey'] = true;
        }

        if ($this->labelKey) {
            $data['labelKey'] = true;
        }

        if ($this->generators !== null) {
            $data['generations'] = $this->generators->toArray();
        }

        if ($this->validators !== null) {
            $data['validations'] = $this->validators->toArray();
        }

        if ($this->options !== null) {
            $data['options'] = $this->options->toArray();
        }

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        if ($this->transform !== null) {
            $data['transform'] = $this->transform;
        }

        return $data;
    }
} 