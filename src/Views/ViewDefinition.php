<?php

namespace LCSEngine\Views;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;

class ViewDefinition
{
    private string $type;

    private string $name;

    private string $label;

    private string $model;

    private string $selectionKey;

    private array $attributes = [];

    private array $lensSimpleFilters = [];

    private string $selectionType;

    private ?object $scopes;

    private array $expand = [];

    private array $allowedScopes = [];

    private ?object $actions;

    private ?array $entityLayout;

    private mixed $serialize;

    public function __construct(string $name, string $label, string $modelName, array $attributes, array $lensSimpleFilters, string $selectionType, ?object $scopes, string $selectionKey, array $expand, array $allowedScopes, object $actions, mixed $serialize, array $fullEntityLayout)
    {
        $this->type = 'view';
        $this->name = $name;
        $this->label = $label;
        $this->expand = $expand;
        $this->selectionKey = $selectionKey;
        $this->model = $modelName;
        $this->selectionType = $selectionType ?? 'none';
        $this->attributes = $attributes;
        $this->lensSimpleFilters = $lensSimpleFilters;
        $this->scopes = $scopes ?? new \stdClass;
        $this->allowedScopes = $allowedScopes;
        $this->actions = $actions;
        $this->serialize = $serialize;
        $this->entityLayout = $fullEntityLayout;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function getSelectionKey(): string
    {
        return $this->selectionKey;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAllowedScopes(): array
    {
        return $this->allowedScopes;
    }

    public function hasScope(string $name): bool
    {
        return isset($this->scopes->$name);
    }

    public function getScope(string $name): ?array
    {
        return $this->objectToArray($this->scopes->$name) ?? null;
    }

    public function getScopes(): object
    {
        return $this->scopes;
    }

    public function getActions(): object
    {
        return $this->actions;
    }

    private function objectToArray($obj): array
    {
        return json_decode(json_encode($obj), true);
    }

    public static function fromObject(object $data, RegistryManager $registryManager, Model $model): self
    {
        try {
            $selectionType = 'none';
            $expand = [];
            $allowedScopes = [];
            $actions = new \stdClass;
            $serialize = false;
            $fullEntityLayout = [];
            $attributes = [];
            $lensSimpleFilters = [];

            $viewModel = $registryManager->get('model', $data->model);

            if (! $viewModel) {
                throw new InvalidArgumentException("Model not found: {$data->model}");
            }

            if (isset($data->attributes)) {
                $attributes = $model->getAttributes()->only($data->attributes)->map(fn ($attribute) => $attribute->toArray())->all();
            }

            if (isset($data->lensSimpleFilters)) {
                $lensSimpleFilters = self::generateLensFilter($data, $model, $registryManager);
            }

            if (isset($data->entityLayout)) {
                $fullEntityLayout = self::generateFullEntityLayout($data->entityLayout);
            }

            if (isset($data->selectionType)) {
                $selectionType = $data->selectionType;
            }

            if (isset($data->expand)) {
                $expand = $data->expand;
            }

            if (isset($data->allowedScopes)) {
                $allowedScopes = $data->allowedScopes;
            }

            if (isset($data->actions)) {
                $actions = $data->actions;
            }

            if (isset($data->serialize)) {
                $serialize = $data->serialize;
            }

            $selectionKey = isset($data->selectionKey) ? $data->selectionKey : $viewModel->getPrimaryKey()->getName();

            return new self($data->name, $data->label, $data->model, $attributes, $lensSimpleFilters, $selectionType, $data->scopes ?? null, $selectionKey, $expand, $allowedScopes, $actions, $serialize, $fullEntityLayout);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$data->name} view definition: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$data->name} view definition: ".$e->getMessage());
        }
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['label'], $data['model'], $data['attributes'], $data['lensSimpleFilters'], $data['selectionType'], $data['scopes'], $data['selectionKey'], $data['expand'], $data['allowedScopes'], $data['actions'], $data['serialize'], []);
    }

    public static function fromModel(Model $model, RegistryManager $registryManager): self
    {
        try {
            $defaultView = [];

            // create default view from model
            $attributes = $model->getAttributes()->map(fn ($attribute) => $attribute->toArray())->all();

            $defaultView = [
                'name' => $model->getName().'_default_view',
                'label' => $model->getLabel().' Default View',
                'model' => $model->getName(),
                'attributes' => $attributes,
                'lensSimpleFilters' => [],
                'selectionType' => 'none',
                'expand' => [],
                'selectionKey' => $model->getPrimaryKey()->getName(),
                'scopes' => new \stdClass,
                'allowedScopes' => [],
                'actions' => new \stdClass,
                'serialize' => false,
                'entityLayout' => [],
            ];

            return new self($defaultView['name'], $defaultView['label'], $defaultView['model'], $defaultView['attributes'], $defaultView['lensSimpleFilters'], $defaultView['selectionType'], $defaultView['scopes'], $defaultView['selectionKey'], $defaultView['expand'], $defaultView['allowedScopes'], $defaultView['actions'], $defaultView['serialize'], $defaultView['entityLayout']);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$model->getName()} view definition from model: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$model->getName()} view definition from model: ".$e->getMessage());
        }
    }

    public static function generateLensFilter($data, Model $model, RegistryManager $registryManager): array
    {
        $lensSimpleFilters = [];

        foreach ($data->lensSimpleFilters as $lensSimpleFilter) {
            $depPath = explode('-', $lensSimpleFilter);

            if (count($depPath) > 1) {
                $lastValue = end($depPath);
                $lensSimpleFilters[$lastValue]['type'] = 'enum';
                $restValues = array_slice($depPath, 0, -1);
                $lensSimpleFilters[$lastValue]['dependsOn'] = $restValues;
                $path = explode('.', $lastValue);
                // determine the model
                if (count($path) > 1) {
                    $lastModelName = $path[count($path) - 2];
                    $relatedModel = $registryManager->get('model', $lastModelName);
                    if ($relatedModel) {
                        $lensSimpleFilters[$lastValue]['model'] = $relatedModel->getName();
                        $lensSimpleFilters[$lastValue]['label'] = $relatedModel->getLabel();
                    } else {
                        $lensSimpleFilters[$lastValue]['model'] = $lastModelName;
                        $lensSimpleFilters[$lastValue]['label'] = ucfirst($lastModelName);
                    }
                } else {
                    $lensSimpleFilters[$lastValue]['model'] = $model->getName();
                    if ($model->getAttribute($lastValue)->getType()->value === 'timestamp') {
                        $lensSimpleFilters[$lastValue]['type'] = 'date';
                    } else {
                        $lensSimpleFilters[$lastValue]['type'] = 'enum';
                    }
                    $lensSimpleFilters[$lastValue]['model'] = $model->getName();

                    if (! $model->getAttribute($lastValue)->getOptions()->isEmpty()) {
                        $lensSimpleFilters[$lastValue]['options'] = $model->getAttribute($lastValue)->getOptions()->map(fn ($option) => $option->toArray())->all();
                    }
                    $lensSimpleFilters[$lastValue]['label'] = $model->getLabel() !== null ? $model->getLabel() : ucfirst($path[0]);
                }
            } else {
                $path = explode('.', $lensSimpleFilter);
                // determine the model
                if (count($path) > 1) {
                    $lensSimpleFilters[$lensSimpleFilter]['type'] = 'enum';
                    $lastModelName = $path[count($path) - 2];
                    $relatedModel = $registryManager->get('model', $lastModelName);
                    if ($relatedModel) {
                        $lensSimpleFilters[$lensSimpleFilter]['model'] = $relatedModel->getName();
                        $lensSimpleFilters[$lensSimpleFilter]['label'] = $relatedModel->getLabel();
                    } else {
                        $lensSimpleFilters[$lensSimpleFilter]['model'] = $lastModelName;
                        $lensSimpleFilters[$lensSimpleFilter]['label'] = ucfirst($lastModelName);
                    }
                } else {
                    if ($model->getAttribute($lensSimpleFilter)->getType()->value === 'timestamp') {
                        $lensSimpleFilters[$lensSimpleFilter]['type'] = 'date';
                    } else {
                        $lensSimpleFilters[$lensSimpleFilter]['type'] = 'enum';
                    }
                    $lensSimpleFilters[$lensSimpleFilter]['model'] = $model->getName();

                    if (! $model->getAttribute($lensSimpleFilter)->getOptions()->isEmpty()) {
                        $lensSimpleFilters[$lensSimpleFilter]['options'] = $model->getAttribute($lensSimpleFilter)->getOptions()->map(fn ($option) => $option->toArray())->all();
                    }
                    $lensSimpleFilters[$lensSimpleFilter]['label'] = $model->getLabel() !== null ? $model->getLabel() : ucfirst($path[0]);
                }
            }
        }

        return $lensSimpleFilters;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'model' => $this->model,
            'expand' => $this->expand,
            'attributes' => $this->attributes,
            'lensSimpleFilters' => $this->lensSimpleFilters,
            'selectionType' => $this->selectionType,
            'selectionKey' => $this->selectionKey,
            'allowedScopes' => $this->allowedScopes,
        ];

        if (isset($this->scopes) && ! empty(get_object_vars($this->scopes))) {
            $data['scopes'] = $this->scopes;
        }

        if (isset($this->actions) && ! empty(get_object_vars($this->actions))) {
            $data['actions'] = $this->actions;
        }

        if (isset($this->serialize) && ! empty($this->serialize)) {
            $data['serialize'] = $this->serialize;
        }

        if (isset($this->entityLayout) && ! empty($this->entityLayout)) {
            $data['entityLayout'] = $this->entityLayout;
        }

        return $data;
    }

    public function toObject(): object
    {
        $result = new \stdClass;
        $result->name = $this->name;
        $result->label = $this->label;
        $result->type = $this->type;
        $result->model = $this->model;
        $result->expand = $this->expand;
        $result->attributes = $this->attributes;
        $result->lensSimpleFilters = $this->lensSimpleFilters;
        $result->selectionType = $this->selectionType;
        $result->selectionKey = $this->selectionKey;
        $result->allowedScopes = $this->allowedScopes;

        if (isset($this->scopes) && ! empty(get_object_vars($this->scopes))) {
            $result->scopes = $this->scopes;
        }

        if (isset($this->actions) && ! empty(get_object_vars($this->actions))) {
            $result->actions = $this->actions;
        }

        if (isset($this->serialize) && ! empty($this->serialize)) {
            $result->serialize = $this->serialize;
        }

        if (isset($this->entityLayout) && ! empty($this->entityLayout)) {
            $result->entityLayout = $this->entityLayout;
        }

        return $result;
    }

    public static function generateFullEntityLayout(array $shorthandLayout): array
    {
        $layout = $shorthandLayout['layout'] ?? $shorthandLayout;

        // If layout is a flat array, wrap it in a single section
        if (! array_filter($layout, 'is_array')) {
            return [[
                'fields' => self::processLayoutItems($layout),
            ]];
        }

        return array_map(function ($section) {
            if (! is_array($section) || empty($section)) {
                throw new InvalidArgumentException('Invalid section: Each section must be a non-empty array');
            }

            $sectionData = ['fields' => []];

            // Handle section header if present
            if (is_string($section[0]) && str_starts_with($section[0], '$')) {
                $sectionData['section'] = substr($section[0], 1);
                $section = array_slice($section, 1);
            }

            // Process section items
            $sectionData['fields'] = array_map(function ($item) {
                if (! is_array($item)) {
                    return self::createField($item);
                }

                // Handle nested section
                if (! empty($item) && is_string($item[0]) && str_starts_with($item[0], '$')) {
                    return [
                        'section' => substr($item[0], 1),
                        'fields' => self::processLayoutItems(array_slice($item, 1)),
                    ];
                }

                // Handle column
                return ['fields' => self::processLayoutItems($item)];
            }, $section);

            return $sectionData;
        }, $layout);
    }

    private static function processLayoutItems(array $items): array
    {
        return array_map(function ($item) {
            if (! is_array($item)) {
                return self::createField($item);
            }

            // Handle list fields
            $baseKey = null;
            $subFields = [];

            foreach ($item as $key) {
                if (! preg_match('/^(.+)\[\*\]\.(.+)$/', $key, $matches)) {
                    throw new InvalidArgumentException("Invalid list field syntax: '$key'. Expected format: 'field[*].subfield'");
                }

                [$baseKey, $subKey] = [$matches[1], $matches[2]];
                $subFields[] = self::createField($subKey);
            }

            if (! $baseKey) {
                throw new InvalidArgumentException('No valid list fields provided');
            }

            return [
                'key' => $baseKey,
                'label' => self::generateLabel($baseKey),
                'type' => 'list',
                'fields' => $subFields,
            ];
        }, $items);
    }

    private static function createField(string $key): array
    {
        return [
            'key' => $key,
            'label' => self::generateLabel($key),
            'type' => 'string',
        ];
    }

    private static function generateLabel(string $key): string
    {
        return str_replace('_', ' ', ucwords($key));
    }
}
