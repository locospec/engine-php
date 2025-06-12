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
use LCSEngine\Schemas\Model\Attributes\Attribute;

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
        array $attributeNames,
        ?RegistryManager $registryManager = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::QUERY;
        $this->model = $model;
        $this->attributes = new Collection();
        $this->lensSimpleFilters = new Collection();
        $this->expand = new Collection();
        $this->allowedScopes = new Collection();
        $this->selectionKey = null;
        $this->selectionType = SelectionType::NONE;
        $this->actions = null;
        $this->serialize = null;
        $this->entityLayout = new Collection();
        $this->registryManager = $registryManager ?? new RegistryManager();

        // Validate and set attributes
        $this->setAndValidateAttributes($attributeNames);
    }

    private function setAndValidateAttributes(array $attributeNames): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        $modelAttributes = $model->getAttributes();
        $resolvedAttributes = new Collection();
        $invalidAttributeNames = new Collection();

        foreach ($attributeNames as $attributeName) {
            if ($modelAttributes->has($attributeName)) {
                $resolvedAttributes->put($attributeName, $modelAttributes->get($attributeName));
            } else {
                $invalidAttributeNames->push($attributeName);
            }
        }

        if ($invalidAttributeNames->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "Invalid attributes for model '{$this->model}': " .
                    $invalidAttributeNames->implode(', ')
            );
        }

        $this->attributes = $resolvedAttributes;
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

    public function addAttribute(string $attributeName): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        if (!$model->getAttributes()->has($attributeName)) {
            throw new \InvalidArgumentException(
                "Attribute '{$attributeName}' not found in model '{$this->model}'"
            );
        }

        // Check if the attribute (by name) is already in the collection
        if (!$this->attributes->has($attributeName)) {
            $this->attributes->put($attributeName, $model->getAttributes()->get($attributeName));
        }
    }

    public function removeAttribute(string $attributeName): void
    {
        $this->attributes->forget($attributeName);
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

    public function addAllowedScope(string $scopeName): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        if (!$model->getScopes()->has($scopeName)) {
            throw new \InvalidArgumentException(
                "Scope '{$scopeName}' not found in model '{$this->model}'"
            );
        }

        if (!$this->allowedScopes->contains($scopeName)) {
            $this->allowedScopes->push($scopeName);
        }
    }

    public function removeAllowedScope(string $scopeName): void
    {
        $this->allowedScopes = $this->allowedScopes->filter(
            fn(string $f) => $f !== $scopeName
        )->values();
    }

    public function getAllowedScopes(): Collection
    {
        return $this->allowedScopes;
    }

    private function setAndValidateScopes(array $scopeNames): void
    {
        $model = $this->registryManager->get('model', $this->model);
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$this->model}' not found in registry");
        }

        $modelScopes = $model->getScopes();
        $invalidScopes = new Collection();

        foreach ($scopeNames as $scopeName) {
            if (!$modelScopes->has($scopeName)) {
                $invalidScopes->push($scopeName);
            }
        }

        if ($invalidScopes->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "Invalid scopes for model '{$this->model}': " .
                    $invalidScopes->implode(', ')
            );
        }

        $this->allowedScopes = new Collection($scopeNames);
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

    private static function parseEntityLayoutItem(array|string $itemData): ?EntityLayoutItem
    {
        if (is_string($itemData)) {
            return new FieldItem($itemData);
        }

        if (is_array($itemData)) {
            $firstElement = $itemData[0] ?? null;

            if (is_string($firstElement)) {
                if (str_starts_with($firstElement, '$')) {
                    // SectionItem
                    $header = substr($firstElement, 1);
                    $section = new SectionItem($header);
                    for ($i = 1; $i < count($itemData); $i++) {
                        $subItem = self::parseEntityLayoutItem($itemData[$i]);
                        if ($subItem instanceof ColumnItem) {
                            $section->addColumn($subItem);
                        } elseif ($subItem instanceof FieldItem || $subItem instanceof SectionItem) {
                            // If a FieldItem or SectionItem is found directly under a SectionItem,
                            // it implies an unnamed column containing this item.
                            $unnamedColumn = new ColumnItem();
                            $unnamedColumn->addItem($subItem);
                            $section->addColumn($unnamedColumn);
                        }
                    }
                    return $section;
                } elseif (str_starts_with($firstElement, '@')) {
                    // Named ColumnItem
                    $header = substr($firstElement, 1);
                    $column = new ColumnItem($header);
                    for ($i = 1; $i < count($itemData); $i++) {
                        $subItem = self::parseEntityLayoutItem($itemData[$i]);
                        if ($subItem) {
                            $column->addItem($subItem);
                        }
                    }
                    return $column;
                } else {
                    // Unnamed ColumnItem (array of fields or nested items)
                    $column = new ColumnItem();
                    foreach ($itemData as $subItem) {
                        $parsedSubItem = self::parseEntityLayoutItem($subItem);
                        if ($parsedSubItem) {
                            $column->addItem($parsedSubItem);
                        }
                    }
                    return $column;
                }
            } else {
                // If the first element is not a string (e.g., empty array or another array), treat as unnamed column
                $column = new ColumnItem();
                foreach ($itemData as $subItem) {
                    $parsedSubItem = self::parseEntityLayoutItem($subItem);
                    if ($parsedSubItem) {
                        $column->addItem($parsedSubItem);
                    }
                }
                return $column;
            }
        }

        return null; // Should not happen with valid input
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'model' => $this->model,
            'attributes' => $this->attributes->map(fn(Attribute $attribute) => $attribute->toArray())->all()
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
                'align' => $this->serialize->getAlign()->value
            ];
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

    public static function fromArray(array $data, ?RegistryManager $registryManager = null): self
    {
        $name = $data['name'] ?? '';
        $label = $data['label'] ?? '';
        $modelName = $data['model'] ?? '';
        $attributes = $data['attributes'] ?? [];

        $query = new self($name, $label, $modelName, $attributes, $registryManager);

        if (isset($data['lensSimpleFilters'])) {
            $query->lensSimpleFilters = new Collection($data['lensSimpleFilters']);
        }

        if (isset($data['expand'])) {
            $query->expand = new Collection($data['expand']);
        }

        if (isset($data['allowedScopes'])) {
            $query->setAndValidateScopes($data['allowedScopes']);
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
            foreach ($data['entityLayout'] as $itemData) {
                $parsedItem = self::parseEntityLayoutItem($itemData);
                if ($parsedItem) {
                    $query->addEntityLayoutItem($parsedItem);
                }
            }
        }

        return $query;
    }
}
