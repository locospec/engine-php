<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Type;
use LCSEngine\Schemas\Query\ActionConfig;
use LCSEngine\Schemas\Query\ActionItem;
use LCSEngine\Schemas\Query\ActionOption;
use LCSEngine\Schemas\Query\AlignType;
use LCSEngine\Schemas\Query\ColumnItem;
use LCSEngine\Schemas\Query\EntityLayoutItem;
use LCSEngine\Schemas\Query\FieldItem;
use LCSEngine\Schemas\Query\SectionItem;
use LCSEngine\Schemas\Query\SelectionType;
use LCSEngine\Schemas\Query\SerializeConfig;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;

class Query
{
    private string $name;
    private string $label;
    private Type $type;
    private string $model;
    private Collection $attributes;
    private Collection $lensSimpleFilters;
    private Collection $expand;
    private Collection $allowedScopes;
    private ?string $selectionKey;
    private SelectionType $selectionType;
    private ?ActionConfig $actions;
    private ?SerializeConfig $serialize;
    private Collection $entityLayout;
    private RegistryManager $registryManager;

    public function __construct(
        string $name,
        string $label,
        string $model,
        Collection $attributes,
        ?RegistryManager $registryManager = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::QUERY;
        $this->model = $model;
        $this->attributes = $attributes;
        $this->lensSimpleFilters = new Collection();
        $this->expand = new Collection();
        $this->allowedScopes = new Collection();
        $this->selectionKey = null;
        $this->selectionType = SelectionType::NONE;
        $this->actions = null;
        $this->serialize = null;
        $this->entityLayout = new Collection();
        $this->registryManager = $registryManager ?? new RegistryManager();

        // Validate attributes against model
        $this->validateAttributes();
    }

    private function validateAttributes(): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        $modelAttributes = $model->getAttributes();
        $invalidAttributes = $this->attributes->filter(function ($attribute) use ($modelAttributes) {
            return !$modelAttributes->has($attribute);
        });

        if ($invalidAttributes->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "Invalid attributes for model '{$this->model}': " .
                    $invalidAttributes->implode(', ')
            );
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function addAttribute(string $attribute): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        if (!$model->getAttributes()->has($attribute)) {
            throw new \InvalidArgumentException(
                "Attribute '{$attribute}' not found in model '{$this->model}'"
            );
        }

        if (!$this->attributes->contains($attribute)) {
            $this->attributes->push($attribute);
        }
    }

    public function removeAttribute(string $attr): void
    {
        $this->attributes = $this->attributes->filter(
            fn(string $attribute) => $attribute !== $attr
        )->values();
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addLensFilter(string $filter): void
    {
        $this->lensSimpleFilters->push($filter);
    }

    public function removeLensFilter(string $filter): void
    {
        $this->lensSimpleFilters = $this->lensSimpleFilters->filter(
            fn(string $f) => $f !== $filter
        )->values();
    }

    public function getLensFilters(): Collection
    {
        return $this->lensSimpleFilters;
    }

    public function addExpand(string $field): void
    {
        $this->expand->push($field);
    }

    public function removeExpand(string $field): void
    {
        $this->expand = $this->expand->filter(
            fn(string $f) => $f !== $field
        )->values();
    }

    public function getExpand(): Collection
    {
        return $this->expand;
    }

    public function addAllowedScope(string $scope): void
    {
        $this->allowedScopes->push($scope);
    }

    public function removeAllowedScope(string $scope): void
    {
        $this->allowedScopes = $this->allowedScopes->filter(
            fn(string $s) => $s !== $scope
        )->values();
    }

    public function getAllowedScopes(): Collection
    {
        return $this->allowedScopes;
    }

    public function setSelectionKey(string $key): void
    {
        $this->selectionKey = $key;
    }

    public function getSelectionKey(): string
    {
        return $this->selectionKey;
    }

    public function setSelectionType(SelectionType $type): void
    {
        $this->selectionType = $type;
    }

    public function getSelectionType(): SelectionType
    {
        return $this->selectionType;
    }

    public function addEntityLayoutItem(EntityLayoutItem $item): void
    {
        $this->entityLayout->push($item);
    }

    public function removeEntityLayoutItem(EntityLayoutItem $itemToRemove): void
    {
        $this->entityLayout = $this->entityLayout->reject(function ($item) use ($itemToRemove) {
            // Compare based on class and relevant properties (e.g., field for FieldItem, header for SectionItem)
            if ($item instanceof FieldItem && $itemToRemove instanceof FieldItem) {
                return $item->getField() === $itemToRemove->getField();
            } elseif ($item instanceof SectionItem && $itemToRemove instanceof SectionItem) {
                return $item->getHeader() === $itemToRemove->getHeader();
            }
            return false;
        })->values();
    }

    public function getEntityLayout(): Collection
    {
        return $this->entityLayout;
    }

    public function setActions(ActionConfig $config): void
    {
        $this->actions = $config;
    }

    public function getActions(): ?ActionConfig
    {
        return $this->actions;
    }

    public function setSerialize(SerializeConfig $config): void
    {
        $this->serialize = $config;
    }

    public function getSerialize(): ?SerializeConfig
    {
        return $this->serialize;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'model' => $this->model,
            'attributes' => $this->attributes->toArray(),
        ];

        if ($this->lensSimpleFilters->isNotEmpty()) {
            $data['lensSimpleFilters'] = $this->lensSimpleFilters->toArray();
        }

        if ($this->expand->isNotEmpty()) {
            $data['expand'] = $this->expand->toArray();
        }

        if ($this->allowedScopes->isNotEmpty()) {
            $data['allowedScopes'] = $this->allowedScopes->toArray();
        }

        if ($this->selectionType !== SelectionType::NONE) {
            $data['selectionType'] = $this->selectionType->value;
        }

        if ($this->selectionKey !== null) {
            $data['selectionKey'] = $this->selectionKey;
        }

        if ($this->actions !== null) {
            $data['actions'] = [
                'header' => $this->actions->getHeader(),
                'items' => $this->actions->getItems()->map(function (ActionItem $item) {
                    $itemData = [
                        'key' => $item->getKey(),
                        'label' => $item->getLabel(),
                    ];

                    if ($item->getUrl() !== '') {
                        $itemData['url'] = $item->getUrl();
                    }

                    if ($item->getIcon() !== '') {
                        $itemData['icon'] = $item->getIcon();
                    }

                    if ($item->getConfirmation()) {
                        $itemData['confirmation'] = true;
                    }

                    if ($item->getOptions()->isNotEmpty()) {
                        $itemData['options'] = $item->getOptions()->map(function (ActionOption $option) {
                            return [
                                'key' => $option->getKey(),
                                'label' => $option->getLabel(),
                                'url' => $option->getUrl(),
                            ];
                        })->toArray();
                    }

                    return $itemData;
                })->toArray(),
            ];
        }

        if ($this->serialize !== null) {
            $data['serialize'] = [
                'header' => $this->serialize->getHeader(),
            ];

            if ($this->serialize->getAlign() !== AlignType::LEFT) {
                $data['serialize']['align'] = $this->serialize->getAlign()->value;
            }
        }

        if ($this->entityLayout->isNotEmpty()) {
            $data['entityLayout'] = $this->entityLayout->map(function ($item) {
                $result = $item->toArray();
                // If it's a FieldItem, return just the field string
                if ($item instanceof FieldItem) {
                    return $result[0];
                }
                return $result;
            })->toArray();
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        $attributes = new Collection($data['attributes'] ?? []);
        $query = new self(
            $data['name'],
            $data['label'],
            $data['model'],
            $attributes
        );

        if (isset($data['lensSimpleFilters'])) {
            $query->lensSimpleFilters = new Collection($data['lensSimpleFilters']);
        }

        if (isset($data['expand'])) {
            $query->expand = new Collection($data['expand']);
        }

        if (isset($data['allowedScopes'])) {
            $query->allowedScopes = new Collection($data['allowedScopes']);
        }

        if (isset($data['selectionKey'])) {
            $query->setSelectionKey($data['selectionKey']);
        }

        if (isset($data['selectionType'])) {
            $query->setSelectionType(SelectionType::from($data['selectionType']));
        }

        if (isset($data['actions'])) {
            $query->setActions(ActionConfig::fromArray($data['actions']));
        }

        if (isset($data['serialize'])) {
            $query->setSerialize(SerializeConfig::fromArray($data['serialize']));
        }

        if (isset($data['entityLayout'])) {
            foreach ($data['entityLayout'] as $item) {
                if (is_string($item)) {
                    $query->addEntityLayoutItem(new FieldItem($item));
                } elseif (is_array($item)) {
                    $header = $item[0];
                    if (str_starts_with($header, '$')) {
                        $section = new SectionItem(substr($header, 1));
                        for ($i = 1; $i < count($item); $i++) {
                            $columnData = $item[$i];
                            if (is_array($columnData)) {
                                $columnName = substr($columnData[0], 1);
                                $column = new ColumnItem($columnName);
                                for ($j = 1; $j < count($columnData); $j++) {
                                    $column->addItem(new FieldItem($columnData[$j]));
                                }
                                $section->addColumn($column);
                            }
                        }
                        $query->addEntityLayoutItem($section);
                    }
                }
            }
        }

        return $query;
    }
}
