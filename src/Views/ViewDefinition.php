<?php

namespace Locospec\Engine\Views;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Models\Relationships\BelongsTo;
use Locospec\Engine\Registry\RegistryManager;

class ViewDefinition
{
    private string $type;

    private string $name;

    private string $label;

    private string $model;

    private array $attributes = [];

    private array $lensSimpleFilters = [];

    private string $selectionType;

    private ?object $scopes;

    public function __construct(string $name, string $label, string $modelName, array $attributes, array $lensSimpleFilters, string $selectionType, ?object $scopes)
    {
        $this->type = 'view';
        $this->name = $name;
        $this->label = $label;
        $this->model = $modelName;
        $this->selectionType = $selectionType ?? 'none';
        $this->attributes = $attributes;
        $this->lensSimpleFilters = $lensSimpleFilters;
        $this->scopes = $scopes ?? new \stdClass;
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

    public function getAttributes(): array
    {
        return $this->attributes;
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

    private function objectToArray($obj): array
    {
        return json_decode(json_encode($obj), true);
    }

    public static function fromObject(object $data, RegistryManager $registryManager, ModelDefinition $model): self
    {
        try {
            $selectionType = 'none';
            $viewModel = $registryManager->get('model', $data->model);

            if (! $viewModel) {
                throw new InvalidArgumentException("Model not found: {$data->model}");
            }

            $attributes = $model->getAttributes()->getAttributesByNames($data->attributes);
            $aliases = array_keys((array) $model->getAliases());
            if (! empty($aliases)) {
                foreach ($aliases as $alias) {
                    if (in_array($alias, $data->attributes)) {
                        $attributes[$alias] = [
                            'type' => 'string',
                            'label' => ucwords(str_replace('_', ' ', $alias)),
                        ];
                    }
                }
            }
            $lensSimpleFilters = self::generateLensFilter($data, $model, $registryManager);

            if (isset($data->selectionType)) {
                $selectionType = $data->selectionType;
            }

            return new self($data->name, $data->label, $data->model, $attributes, $lensSimpleFilters, $selectionType, $data->scopes ?? null);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$data->name} view definition: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$data->name} view definition: ".$e->getMessage());
        }
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['label'], $data['model'], $data['attributes'], $data['lensSimpleFilters'], $data['selectionType'], $data['scopes']);
    }

    public static function fromModel(ModelDefinition $model, object $spec, RegistryManager $registryManager): self
    {
        try {
            $defaultView = [];

            // create default view from model
            $attributes = $model->getAttributes()->toArray();
            $aliases = array_keys((array) $model->getAliases());

            if (! empty($aliases)) {
                foreach ($aliases as $alias) {
                    $attributes[$alias] = [
                        'type' => 'string',
                        'label' => ucwords(str_replace('_', ' ', $alias)),
                    ];
                }
            }

            $defaultView = [
                'name' => $model->getName().'_default_view',
                'label' => $model->getLabel().' Default View',
                'model' => $model->getName(),
                'attributes' => $attributes,
                'lensSimpleFilters' => [],
                'selectionType' => 'none',
                'scopes' => $model->getScopes() ?? new \stdClass,
            ];

            return new self($defaultView['name'], $defaultView['label'], $defaultView['model'], $defaultView['attributes'], $defaultView['lensSimpleFilters'], $defaultView['selectionType'], $defaultView['scopes']);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$spec->name} view definition from model: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$spec->name} view definition from model: ".$e->getMessage());
        }
    }

    public static function generateLensFilter($data, ModelDefinition $model, RegistryManager $registryManager): array
    {
        $lensSimpleFilters = [];

        foreach ($data->lensSimpleFilters as $lensSimpleFilter) {
            $path = explode('.', $lensSimpleFilter);
            if (count($path) === 2) {
                $relatedModel = $registryManager->get('model', $path[0]);
                // instanceof BelongsTo
                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => $relatedModel->getLabel().' '.ucfirst($path[1]),
                    'model' => $path[0],
                ];
            } elseif (count($path) === 1) {
                $dependsOn = [];
                if (! empty($model->getRelationships())) {
                    foreach ($model->getRelationships() as $key => $relationship) {
                        if ($relationship instanceof BelongsTo) {
                            $relationshipModel = $registryManager->get('model', $relationship->getRelatedModelName());
                            $dependsOn[] = $key.'.'.$relationshipModel->getConfig()->getLabelKey();
                        }
                    }
                }

                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => ucfirst($path[0]),
                    'model' => $model->getName(),
                ];

                if (! empty($model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['options'])) {
                    $lensSimpleFilters[$lensSimpleFilter]['options'] = $model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['options'];
                }

                if ($model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['type'] === 'timestamp') {
                    $lensSimpleFilters[$lensSimpleFilter]['type'] = 'date';
                }

                if (isset($model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['label'])) {
                    $lensSimpleFilters[$lensSimpleFilter]['label'] = $model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['label'];
                }

                if (! empty($dependsOn)) {
                    $lensSimpleFilters[$lensSimpleFilter]['dependsOn'] = $dependsOn;
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
            'attributes' => $this->attributes,
            'lensSimpleFilters' => $this->lensSimpleFilters,
            'selectionType' => $this->selectionType,
        ];

        if (isset($this->scopes) && ! empty(get_object_vars($this->scopes))) {
            $data['scopes'] = $this->scopes;
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
        $result->attributes = $this->attributes;
        $result->lensSimpleFilters = $this->lensSimpleFilters;
        $result->selectionType = $this->selectionType;

        if (isset($this->scopes) && ! empty(get_object_vars($this->scopes))) {
            $result->scopes = $this->scopes;
        }

        return $result;
    }
}
