<?php

namespace LCSEngine\Views;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Model\Relationships\BelongsTo;

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

    private mixed $serialize;

    public function __construct(string $name, string $label, string $modelName, array $attributes, array $lensSimpleFilters, string $selectionType, ?object $scopes, string $selectionKey, array $expand, array $allowedScopes, object $actions, mixed $serialize)
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

            $viewModel = $registryManager->get('model', $data->model);

            if (! $viewModel) {
                throw new InvalidArgumentException("Model not found: {$data->model}");
            }

            $attributes = $model->getAttributes()->only($data->attributes)->map(fn ($attribute) => $attribute->toArray())->all();
            // ->getAttributesByNames($data->attributes);
            // $aliases = array_keys((array) $model->getAliases());
            // if (! empty($aliases)) {
            //     foreach ($aliases as $alias) {
            //         if (in_array($alias, $data->attributes)) {
            //             $attributes[$alias] = [
            //                 'type' => 'string',
            //                 'label' => ucwords(str_replace('_', ' ', $alias)),
            //             ];
            //         }
            //     }
            // }
            $lensSimpleFilters = self::generateLensFilter($data, $model, $registryManager);

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

            return new self($data->name, $data->label, $data->model, $attributes, $lensSimpleFilters, $selectionType, $data->scopes ?? null, $selectionKey, $expand, $allowedScopes, $actions, $serialize);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$data->name} view definition: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$data->name} view definition: ".$e->getMessage());
        }
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['label'], $data['model'], $data['attributes'], $data['lensSimpleFilters'], $data['selectionType'], $data['scopes'], $data['selectionKey'], $data['expand'], $data['allowedScopes'], $data['actions'], $data['serialize']);
    }

    public static function fromModel(Model $model, RegistryManager $registryManager): self
    {
        try {
            $defaultView = [];

            // create default view from model
            $attributes = $model->getAttributes()->map(fn ($attribute) => $attribute->toArray())->all();
            // $aliases = array_keys((array) $model->getAliases());

            // if (! empty($aliases)) {
            //     foreach ($aliases as $alias) {
            //         $attributes[$alias] = [
            //             'type' => 'string',
            //             'label' => ucwords(str_replace('_', ' ', $alias)),
            //         ];
            //     }
            // }

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
            ];

            return new self($defaultView['name'], $defaultView['label'], $defaultView['model'], $defaultView['attributes'], $defaultView['lensSimpleFilters'], $defaultView['selectionType'], $defaultView['scopes'], $defaultView['selectionKey'], $defaultView['expand'], $defaultView['allowedScopes'], $defaultView['actions'], $defaultView['serialize']);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Error creating {$spec->name} view definition from model: ".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Unexpected error while creating {$spec->name} view definition from model: ".$e->getMessage());
        }
    }

    public static function generateLensFilter($data, Model $model, RegistryManager $registryManager): array
    {
        $lensSimpleFilters = [];

        foreach ($data->lensSimpleFilters as $lensSimpleFilter) {
            $path = explode('.', $lensSimpleFilter);
            if (count($path) > 1) {
                $lastModelName = $path[count($path) - 2];
                $relatedModel = $registryManager->get('model', $lastModelName);
                // instanceof BelongsTo
                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => $relatedModel->getLabel(),
                    'model' => $lastModelName,
                ];
            } elseif (count($path) === 1) {
                $dependsOn = [];
                if (! empty($model->getRelationships())) {
                    foreach ($model->getRelationships() as $key => $relationship) {
                        if ($relationship instanceof BelongsTo) {
                            $relationshipModel = $registryManager->get('model', $relationship->getRelatedModelName());
                            $dependsOn[] = $key.'.'.$relationshipModel->getPrimaryKey()->getName();
                        }
                    }
                }

                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => ucfirst($path[0]),
                    'model' => $model->getName(),
                ];

                if (! $model->getAttribute($lensSimpleFilter)->getOptions()->isEmpty()) {
                    // $lensSimpleFilters[$lensSimpleFilter]['options'] = $model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['options'];
                    $lensSimpleFilters[$lensSimpleFilter]['options'] = $model->getAttribute($lensSimpleFilter)->getOptions()->all();
                }

                if ($model->getAttribute($lensSimpleFilter)->getType()->value === 'timestamp') {
                    $lensSimpleFilters[$lensSimpleFilter]['type'] = 'date';
                }

                $lensSimpleFilters[$lensSimpleFilter]['label'] = $model->getAttribute($lensSimpleFilter)->getLabel();

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

        return $result;
    }
}
