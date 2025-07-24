<?php

namespace LCSEngine\Schemas\Model\Attributes;

use Illuminate\Support\Collection;

class Attribute
{
    private string $name;

    private string $label;

    private Type $type;

    private Collection $generators;

    private Collection $validators;

    private Collection $options;

    private Collection $dependsOn;

    private bool $aliasKey = false;

    private bool $primaryKey = false;

    private bool $deleteKey = false;

    private bool $labelKey = false;

    private bool $transformKey = false;

    private ?string $source = null;

    private ?string $transform = null;

    private ?string $relatedModelName = null;

    private ?string $optionsAggregator = null;

    public function __construct(string $name, string $label, Type $type)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->generators = collect();
        $this->validators = collect();
        $this->options = collect();
        $this->dependsOn = collect();
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAliasKey(bool $flag): void
    {
        $this->aliasKey = $flag;
    }

    public function setPrimaryKey(bool $flag): void
    {
        $this->primaryKey = $flag;
    }

    public function setLabelKey(bool $flag): void
    {
        $this->labelKey = $flag;
    }

    public function setDeleteKey(bool $flag): void
    {
        $this->deleteKey = $flag;
    }

    public function setTransformKey(bool $flag): void
    {
        $this->transformKey = $flag;
    }

    public function setAliasSource(string $source): void
    {
        if (! $this->aliasKey) {
            throw new \LogicException('Cannot set alias source: attribute type is not ALIAS.');
        }

        $this->source = $source;
    }

    public function setAliasTransformation(string $transform): void
    {
        if (! $this->aliasKey) {
            throw new \LogicException('Cannot set alias transformation: attribute type is not ALIAS.');
        }

        $this->transform = $transform;
    }

    public function setTransformSource(string $source): void
    {
        if (! $this->transformKey) {
            throw new \LogicException('Cannot set transform source: attribute type is not TRANSFORM.');
        }

        $this->source = $source;
    }

    public function setTransformTransformation(string $transform): void
    {
        if (! $this->transformKey) {
            throw new \LogicException('Cannot set transform transformation: attribute type is not TRANSFORM.');
        }

        $this->transform = $transform;
    }

    public function setRelatedModelName(?string $relatedModelName): void
    {
        $this->relatedModelName = $relatedModelName;
    }

    public function setOptionsAggregator(?string $optionsAggregator): void
    {
        $this->optionsAggregator = $optionsAggregator;
    }

    public function setDependsOn(string $dependsOn): void
    {
        $this->dependsOn->push($dependsOn);
    }

    public function isAliasKey(): bool
    {
        return $this->aliasKey;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function isLabelKey(): bool
    {
        return $this->labelKey;
    }

    public function isDeleteKey(): bool
    {
        return $this->deleteKey;
    }

    public function isTransformKey(): bool
    {
        return $this->transformKey;
    }

    public function addGenerator(Generator $generator): void
    {
        $generator->setId((string) ($this->generators->count() + 1));
        $this->generators->push($generator);
    }

    public function addValidator(Validator $validator): void
    {
        $validator->setId((string) ($this->validators->count() + 1));
        $this->validators->push($validator);
    }

    public function addOption(Option $option): void
    {
        $option->setId((string) ($this->options->count() + 1));
        $this->options->push($option);
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGenerators(): Collection
    {
        return $this->generators;
    }

    public function getValidators(): Collection
    {
        return $this->validators;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function getAliasSource(): ?string
    {
        return $this->aliasKey ? $this->source : null;
    }

    public function hasAliasSource(): bool
    {
        return $this->aliasKey && $this->source !== null;
    }

    public function hasAliasTransformation(): bool
    {
        return $this->aliasKey && $this->transform !== null;
    }

    public function getAliasTransformation(): ?string
    {
        return $this->aliasKey ? $this->transform : null;
    }

    public function getTransformSource(): ?string
    {
        return $this->transformKey ? $this->source : null;
    }

    public function hasTransformSource(): bool
    {
        return $this->transformKey && $this->source !== null;
    }

    public function hasTransformTransformation(): bool
    {
        return $this->transformKey && $this->transform !== null;
    }

    public function getTransformTransformation(): ?string
    {
        return $this->transformKey ? $this->transform : null;
    }

    public function getRelatedModelName(): ?string
    {
        return $this->relatedModelName;
    }

    public function getOptionsAggregator(): ?string
    {
        return $this->optionsAggregator;
    }

    public function getDependsOn(): Collection
    {
        return $this->dependsOn;
    }

    public function removeGeneratorById(string $id): void
    {
        $this->generators = $this->generators->reject(fn ($g) => $g->getId() === $id)->values();
    }

    public function removeValidatorById(string $id): void
    {
        $this->validators = $this->validators->reject(fn ($v) => $v->getId() === $id)->values();
    }

    public function removeOptionById(string $id): void
    {
        $this->options = $this->options->reject(fn ($o) => $o->getId() === $id)->values();
    }

    public static function fromArray(string $name, array $data): self
    {
        $name = $data['name'] ?? $name;
        $label = $data['label'] ?? '';
        $type = Type::from($data['type'] ?? 'string');
        $aliasKey = $data['aliasKey'] ?? false;
        $attribute = new self($name, $label, $type);

        // Boolean flags
        if (isset($data['primaryKey'])) {
            $attribute->setPrimaryKey((bool) $data['primaryKey']);
        }
        if (isset($data['aliasKey'])) {
            $attribute->setAliasKey((bool) $data['aliasKey']);
        }
        if (isset($data['labelKey'])) {
            $attribute->setLabelKey((bool) $data['labelKey']);
        }
        if (isset($data['deleteKey'])) {
            $attribute->setDeleteKey((bool) $data['deleteKey']);
        }
        if (isset($data['transformKey'])) {
            $attribute->setTransformKey((bool) $data['transformKey']);
        }

        // Alias fields
        if ($aliasKey) {
            if (isset($data['source'])) {
                $attribute->setAliasSource($data['source']);
            }
            if (isset($data['transform'])) {
                $attribute->setAliasTransformation($data['transform']);
            }
        }

        // Transform fields
        if (isset($data['transformKey']) && $data['transformKey']) {
            if (isset($data['source'])) {
                $attribute->setTransformSource($data['source']);
            }
            if (isset($data['transform'])) {
                $attribute->setTransformTransformation($data['transform']);
            }
        }

        // Generators
        if (! empty($data['generators']) && is_array($data['generators'])) {
            foreach ($data['generators'] as $generatorData) {
                $attribute->addGenerator(
                    is_object($generatorData) ? $generatorData : Generator::fromArray($generatorData)
                );
            }
        }

        // Validators
        if (! empty($data['validators']) && is_array($data['validators'])) {
            foreach ($data['validators'] as $validatorData) {
                $attribute->addValidator(
                    is_object($validatorData) ? $validatorData : Validator::fromArray($validatorData)
                );
            }
        }

        // Options
        if (! empty($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $attribute->addOption(
                    is_object($optionData) ? $optionData : Option::fromArray($optionData)
                );
            }
        }

        if (isset($data['relatedModelName'])) {
            $attribute->setRelatedModelName($data['relatedModelName']);
        }

        if (isset($data['optionsAggregator'])) {
            $attribute->setOptionsAggregator($data['optionsAggregator']);
        }

        if (! empty($data['dependsOn']) && is_array($data['dependsOn'])) {
            foreach ($data['dependsOn'] as $dependsOnData) {
                $attribute->setDependsOn($dependsOnData);
            }
        }

        return $attribute;
    }

    public function toArray(): array
    {
        $arr = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'aliasKey' => $this->aliasKey,
            'primaryKey' => $this->primaryKey,
            'labelKey' => $this->labelKey,
            'deleteKey' => $this->deleteKey,
            'transformKey' => $this->transformKey,
        ];

        if ($this->relatedModelName !== null) {
            $arr['relatedModelName'] = $this->relatedModelName;
        }

        if ($this->optionsAggregator !== null) {
            $arr['optionsAggregator'] = $this->optionsAggregator;
        }

        if (! $this->dependsOn->isEmpty()) {
            $arr['dependsOn'] = $this->dependsOn->all();
        }

        if (! $this->options->isEmpty()) {
            $arr['options'] = $this->options->map(fn ($o) => $o->toArray())->all();
        }

        if (! $this->generators->isEmpty()) {
            $arr['generators'] = $this->generators->map(fn ($g) => $g->toArray())->all();
        }

        if (! $this->validators->isEmpty()) {
            $arr['validators'] = $this->validators->map(fn ($v) => $v->toArray())->all();
        }

        if ($this->aliasKey && $this->source !== null) {
            $arr['source'] = $this->source;
        }

        if ($this->aliasKey && $this->transform !== null) {
            $arr['transform'] = $this->transform;
        }

        if ($this->transformKey && $this->source !== null) {
            $arr['source'] = $this->source;
        }

        if ($this->transformKey && $this->transform !== null) {
            $arr['transform'] = $this->transform;
        }

        return $arr;
    }
}
