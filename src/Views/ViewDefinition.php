<?php

namespace Locospec\Engine\Views;

use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\RegistryManager;
use Locospec\Engine\Models\Relationships\BelongsTo;

class ViewDefinition
{
    private string $type;

    private string $name;

    private string $label;

    private string $model;

    private array $attributes = [];

    private array $lensSimpleFilters = [];

    public function __construct(string $name, string $label, string $modelName, array $attributes, array $lensSimpleFilters)
    {
        $this->type = 'view';
        $this->name = $name;
        $this->label = $label;
        $this->model = $modelName;
        $this->attributes = $attributes;
        $this->lensSimpleFilters = $lensSimpleFilters;
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

    public static function fromObject(object $data, RegistryManager $registryManager, ModelDefinition $model): self
    {
        $viewModel = $registryManager->get('model', $data->model);

        if (! $viewModel) {
            throw new InvalidArgumentException("Model not found: {$data->model}");
        }

        $attributes = $model->getAttributes()->getAttributesByNames($data->attributes);
        $lensSimpleFilters = self::generateLensFilter($data, $model, $registryManager);

        return new self($data->name, $data->label, $data->model, $attributes, $lensSimpleFilters);
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['label'], $data['model'], $data['attributes'], $data['lensSimpleFilters']);
    }

    public static function fromModel(ModelDefinition $model, object $spec, RegistryManager $registryManager): self
    {
        $defaultView = [];

        if (isset($spec->defaultView)) {
            // create default view when defaultView is in model
            $attributes = $model->getAttributes()->getAttributesByNames($spec->defaultView->attributes);
            $aliases = array_keys((array) $model->getAliases());
             if(!empty($aliases)){
                foreach ($aliases as $alias) {
                    if(in_array($alias, $spec->defaultView->attributes)){
                        $attributes[$alias] = [
                            'type' => 'string',
                            'label' => ucwords(str_replace('_', ' ', $alias))
                        ];
                    }
                }
            }

            $defaultView = [
                "name" => $spec->defaultView->name,
                "label" => $spec->defaultView->label,
                "model" => $model->getName(),
                "attributes" => $attributes,
                "lensSimpleFilters" => self::generateLensFilter($spec->defaultView, $model, $registryManager)
            ];
        } else {
            // create default view from model
            $attributes = $model->getAttributes()->toArray();
            $aliases = array_keys((array) $model->getAliases());

            if(!empty($aliases)){
                foreach ($aliases as $alias) {
                    $attributes[$alias] = [
                        'type' => 'string',
                        'label' => ucwords(str_replace('_', ' ', $alias))
                    ];
                }
            }

            $defaultView = [
                "name" => $model->getName()."_default_view",
                "label" => $model->getLabel()." Default View",
                "model" => $model->getName(),
                "attributes" => $attributes,
                "lensSimpleFilters" => [],
            ];
        }

        return new self($defaultView['name'], $defaultView['label'], $defaultView['model'], $defaultView['attributes'], $defaultView['lensSimpleFilters']);
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
                    'label' => $relatedModel->getLabel()." ".ucfirst($path[1]),
                    'model' => $path[0],
                ];
            }elseif(count($path) === 1){
                $dependsOn = [];
                // dump("check relations", $model->getAliases(), $model->getName(), $model->getRelationships());
                if(!empty($model->getRelationships())){
                    foreach ($model->getRelationships() as $key => $relationship) {
                        if($relationship instanceof BelongsTo){
                            $relationshipModel = $registryManager->get('model', $relationship->getRelatedModelName());
                            $dependsOn[] = $key.".".$relationshipModel->getConfig()->getLabelKey();
                        }
                    }
                }
                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => ucfirst($path[0]),
                    'model' => $model->getName(),
                ];

                if(!empty($model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['options'])){
                   $lensSimpleFilters[$lensSimpleFilter]['options'] = $model->getAttributes()->getAttributesByNames([$lensSimpleFilter])[$lensSimpleFilter]['options'];
                }

                if(!empty($dependsOn)){
                    $lensSimpleFilters[$lensSimpleFilter]['dependsOn'] = $dependsOn;
                }
            }
        }

        return $lensSimpleFilters;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'model' => $this->model,
            'attributes' => $this->attributes,
            'lensSimpleFilters' => $this->lensSimpleFilters,
        ];
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

        return $result;
    }
}
