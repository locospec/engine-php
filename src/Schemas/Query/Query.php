<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Type;

class Query
{
    private string $name;
    private string $label;
    private string $model;
    private ?string $selectionKey;
    private Type $type;
    private Collection $attributes;
    private Collection $lensSimpleFilters;
    private Collection $expand;
    private Collection $allowedScopes;
    private Collection $entityLayout;
    private ?ActionConfig $actions;
    private ?SerializeConfig $serialize;
    private SelectionType $selectionType;

    public function __construct(
        string $name,
        string $label,
        string $model,
        Collection $attributes
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->model = $model;
        $this->attributes = $attributes;
        $this->type = Type::QUERY;
        $this->selectionType = SelectionType::NONE;
        $this->lensSimpleFilters = new Collection();
        $this->expand = new Collection();
        $this->allowedScopes = new Collection();
        $this->entityLayout = new Collection();
        $this->actions = null;
        $this->serialize = null;
        $this->selectionKey = null;
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

    public function addAttribute(string $attr): void
    {
        $this->attributes->push($attr);
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
        $attributes = new Collection($data['attributes']);
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
                    if (isset($item[0]) && is_string($item[0]) && str_starts_with($item[0], '$')) {
                        $header = substr($item[0], 1);
                        $section = new SectionItem($header);

                        // Process columns
                        for ($i = 1; $i < count($item); $i++) {
                            $columnData = $item[$i];
                            if (is_array($columnData)) {
                                $column = new ColumnItem();

                                // Check if first element is a column header
                                if (isset($columnData[0]) && is_string($columnData[0]) && str_starts_with($columnData[0], '@')) {
                                    $column = new ColumnItem(substr($columnData[0], 1));
                                    array_shift($columnData);
                                }

                                // Process column items
                                foreach ($columnData as $columnItem) {
                                    if (is_string($columnItem)) {
                                        $column->addItem(new FieldItem($columnItem));
                                    } elseif (is_array($columnItem)) {
                                        // Handle nested sections
                                        if (isset($columnItem[0]) && is_string($columnItem[0]) && str_starts_with($columnItem[0], '$')) {
                                            $nestedHeader = substr($columnItem[0], 1);
                                            $nestedSection = new SectionItem($nestedHeader);

                                            // Process nested columns
                                            for ($j = 1; $j < count($columnItem); $j++) {
                                                $nestedColumnData = $columnItem[$j];
                                                if (is_array($nestedColumnData)) {
                                                    $nestedColumn = new ColumnItem();

                                                    // Check if first element is a column header
                                                    if (isset($nestedColumnData[0]) && is_string($nestedColumnData[0]) && str_starts_with($nestedColumnData[0], '@')) {
                                                        $nestedColumn = new ColumnItem(substr($nestedColumnData[0], 1));
                                                        array_shift($nestedColumnData);
                                                    }

                                                    // Add nested column items
                                                    foreach ($nestedColumnData as $nestedItem) {
                                                        if (is_string($nestedItem)) {
                                                            $nestedColumn->addItem(new FieldItem($nestedItem));
                                                        }
                                                    }

                                                    $nestedSection->addColumn($nestedColumn);
                                                }
                                            }

                                            $column->addItem($nestedSection);
                                        }
                                    }
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
