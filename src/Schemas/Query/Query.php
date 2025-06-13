<?php

namespace LCSEngine\Schemas\Query;

use Illuminate\Support\Collection;
use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Query\ActionConfig\ActionConfig;
use LCSEngine\Schemas\Query\ActionConfig\ActionItem;
use LCSEngine\Schemas\Query\ActionConfig\ActionOption;
use LCSEngine\Schemas\Query\EntityLayout\EntityLayoutBuilder;
use LCSEngine\Schemas\Query\LensSimpleFilter\LensSimpleFilter;
use LCSEngine\Schemas\Type;

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

    public function __construct(
        string $name,
        string $label,
        array $attributeNames,
        Model $model
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::QUERY;
        $this->model = $model->getName();
        $this->attributes = new Collection;
        $this->lensSimpleFilters = new Collection;
        $this->expand = new Collection;
        $this->allowedScopes = new Collection;
        $this->selectionKey = null;
        $this->selectionType = SelectionType::NONE;
        $this->actions = null;
        $this->serialize = null;
        $this->entityLayout = new Collection;

        // Validate and set attributes
        $this->setAndValidateAttributes($attributeNames, $model);
    }

    private function setAndValidateAttributes(array $attributeNames, Model $model): void
    {
        $modelAttributes = $model->getAttributes();
        $resolvedAttributes = new Collection;
        $invalidAttributeNames = new Collection;

        foreach ($attributeNames as $attributeName) {
            if ($modelAttributes->has($attributeName)) {
                $resolvedAttributes->put($attributeName, $modelAttributes->get($attributeName));
            } else {
                $invalidAttributeNames->push($attributeName);
            }
        }

        if ($invalidAttributeNames->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "Invalid attributes for model '{$this->model}': ".
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

    public function getModelName(): string
    {
        return $this->model;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function addAttribute(string $attributeName, Model $model): void
    {
        if (! $model->getAttributes()->has($attributeName)) {
            throw new \InvalidArgumentException(
                "Attribute '{$attributeName}' not found in model '{$this->model}'"
            );
        }

        // Check if the attribute (by name) is already in the collection
        if (! $this->attributes->has($attributeName)) {
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

    public function addLensFilter(LensSimpleFilter $lensFilter): void
    {
        $this->lensSimpleFilters->put($lensFilter->getName(), $lensFilter);
    }

    public function removeLensFilter(string $lensFilterName): void
    {
        $this->lensSimpleFilters->forget($lensFilterName);
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
            fn (string $f) => $f !== $field
        )->values();
    }

    public function getExpand(): Collection
    {
        return $this->expand;
    }

    public function addAllowedScope(string $scopeName, Model $model): void
    {
        if (! $model->getScopes()->has($scopeName)) {
            throw new \InvalidArgumentException(
                "Scope '{$scopeName}' not found in model '{$model->getName()}'"
            );
        }

        if (! $this->allowedScopes->contains($scopeName)) {
            $this->allowedScopes->push($scopeName);
        }
    }

    public function removeAllowedScope(string $scopeName): void
    {
        $this->allowedScopes = $this->allowedScopes->filter(
            fn (string $f) => $f !== $scopeName
        )->values();
    }

    public function getAllowedScopes(): Collection
    {
        return $this->allowedScopes;
    }

    private function setAndValidateScopes(array $scopeNames, Model $model): void
    {
        $modelScopes = $model->getScopes();
        $invalidScopes = new Collection;

        foreach ($scopeNames as $scopeName) {
            if (! $modelScopes->has($scopeName)) {
                $invalidScopes->push($scopeName);
            }
        }

        if ($invalidScopes->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "Invalid scopes for model '{$model->getName()}': ".
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

    public function setEntityLayout(Collection $layout): void
    {
        $this->entityLayout = $layout;
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

    public static function generateLensFilter(array $data, Model $model, RegistryManager $registryManager): array
    {
        $lensSimpleFilters = [];

        foreach ($data as $filter) {
            // 1) split on "-" to detect dependencies
            $parts = explode('-', $filter);
            $hasDeps = count($parts) > 1;
            $depends = $hasDeps ? array_slice($parts, 0, -1) : [];
            $key = $hasDeps ? end($parts) : $filter;

            // 2) split on "." to see if this lives on a related model
            $path = explode('.', $key);
            if (count($path) > 1) {
                // related-model case
                $relatedName = $path[count($path) - 2];
                $related = $registryManager->get('model', $relatedName);

                $type = 'enum';
                $modelId = $related ? $related->getName() : $relatedName;
                $label = $related ? $related->getLabel() : ucfirst($relatedName);

                $options = null;
            } else {
                // same-model case
                $attr = $model->getAttribute($key);
                $type = $attr->getType()->value === 'timestamp' ? 'date' : 'enum';
                $modelId = $model->getName();

                $opts = $attr->getOptions();
                $options = ! $opts->isEmpty()
                    ? $opts->map(fn ($o) => $o->toArray())->all()
                    : null;

                $label = $model->getLabel() !== null
                    ? $model->getLabel()
                    : ucfirst($path[0]);
            }

            // 3) assemble and drop nulls
            $lensSimpleFilters[$key] = array_filter([
                'type' => $type,
                'model' => $modelId,
                'label' => $label,
                'options' => $options,
                'dependsOn' => $depends ?: null,
            ], fn ($v) => $v !== null);
        }

        return $lensSimpleFilters;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'model' => $this->model,
            'attributes' => $this->attributes->map(fn (Attribute $attribute) => $attribute->toArray())->all(),
            'lensSimpleFilters' => $this->lensSimpleFilters->map(fn (LensSimpleFilter $lensFilter) => $lensFilter->toArray())->all(),
            'expand' => $this->expand->toArray(),
            'allowedScopes' => $this->allowedScopes->toArray(),
            'selectionType' => $this->selectionType->value,
            'selectionKey' => $this->selectionKey,
        ];

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
                'align' => $this->serialize->getAlign()->value,
            ];
        }

        if ($this->entityLayout->isNotEmpty()) {
            $data['entityLayout'] = $this->entityLayout->map(
                fn ($section) => $section->toArray()
            )->all();
        }

        return $data;
    }

    public static function fromArray(array $data, RegistryManager $registryManager): self
    {
        $name = $data['name'] ?? '';
        $label = $data['label'] ?? '';
        $modelName = $data['model'] ?? '';
        $attributes = $data['attributes'] ?? [];

        $model = $registryManager->get('model', $modelName);
        if (! $model instanceof Model) {
            throw new \InvalidArgumentException("Model '{$modelName}' not found in registry");
        }

        $query = new self($name, $label, $attributes, $model);

        if (isset($data['lensSimpleFilters'])) {
            $query->lensSimpleFilters = new Collection;
            // Check if it's a shorthand array format
            if (is_array($data['lensSimpleFilters']) && array_is_list($data['lensSimpleFilters'])) {
                $generatedFilters = self::generateLensFilter($data['lensSimpleFilters'], $model, $registryManager);
                foreach ($generatedFilters as $filterName => $filterData) {
                    $thisData = $filterData;
                    $thisData['name'] = $filterName;
                    $query->lensSimpleFilters->put($filterName, LensSimpleFilter::fromArray($thisData));
                }
            } else {
                // Handle the existing object format
                foreach ($data['lensSimpleFilters'] as $filterName => $filterData) {
                    $thisData = $filterData;
                    $thisData['name'] = $filterName;
                    $query->lensSimpleFilters->put($filterName, LensSimpleFilter::fromArray($thisData));
                }
            }
        }

        if (isset($data['expand'])) {
            $query->expand = new Collection($data['expand']);
        }

        if (isset($data['allowedScopes'])) {
            $query->setAndValidateScopes($data['allowedScopes'], $model);
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
            $layoutBuilder = EntityLayoutBuilder::fromShorthand($data['entityLayout']);
            $query->entityLayout = $layoutBuilder->getSections();
        }

        return $query;
    }

    public static function fromModel(Model $model): self
    {
        try {
            $name = $model->getName().'_default_query';
            $label = $model->getLabel().' Default Query';
            $attributes = $model->getAttributes()->keys()->toArray();

            $query = new self($name, $label, $attributes, $model);

            $query->setSelectionKey($model->getPrimaryKey()->getName());
            $query->allowedScopes = new Collection($model->getScopes()->keys()->toArray());
            $query->setSerialize(new SerializeConfig);

            return $query;
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$model->getName()} query definition from model: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$model->getName()} query definition from model: ".$e->getMessage());
        }
    }
}
