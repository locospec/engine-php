<?php

namespace LCSEngine\Schemas\Query\LensSimpleFilter;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Model\Attributes\Option;

class LensSimpleFilter
{
    private LensFilterType $type;

    private string $model;

    private string $name;

    private string $label;

    private Collection $options;

    private Collection $dependsOn;

    public function __construct(string $name, string $type, string $modelName)
    {
        $this->name = $name;
        $this->type = LensFilterType::from($type);
        $this->model = $modelName;
        $this->label = $name;
        $this->options = new Collection;
        $this->dependsOn = new Collection;
    }

    public function setType(LensFilterType $type): void
    {
        $this->type = $type;
    }

    public function getType(): LensFilterType
    {
        return $this->type;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(Option $option): void
    {
        $this->options->push($option);
    }

    public function removeOption(string $optionId): void
    {
        $this->options = $this->options->filter(
            fn (Option $option) => $option->getId() !== $optionId
        )->values();
    }

    public function getDependsOn(): Collection
    {
        return $this->dependsOn;
    }

    public function addDependsOn(string $dependency): void
    {
        if (! $this->dependsOn->contains($dependency)) {
            $this->dependsOn->push($dependency);
        }
    }

    public function removeDependsOn(string $dependency): void
    {
        $this->dependsOn = $this->dependsOn->filter(
            fn (string $d) => $d !== $dependency
        )->values();
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'model' => $this->model,
            'name' => $this->name,
            'label' => $this->label,
        ];

        if ($this->options->isNotEmpty()) {
            $data['options'] = $this->options->map(fn (Option $option) => $option->toArray())->toArray();
        }

        if ($this->dependsOn->isNotEmpty()) {
            $data['dependsOn'] = $this->dependsOn->toArray();
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $lensFilter = new self($data['name'], $data['type'], $data['model']);

        if (isset($data['label'])) {
            $lensFilter->setLabel($data['label']);
        }

        if (isset($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $lensFilter->addOption(Option::fromArray($optionData));
            }
        }

        if (isset($data['dependsOn'])) {
            foreach ($data['dependsOn'] as $dependency) {
                $lensFilter->addDependsOn($dependency);
            }
        }

        return $lensFilter;
    }
}
