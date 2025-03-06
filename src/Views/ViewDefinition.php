<?php

namespace Locospec\Engine\Views;

use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\RegistryManager;

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

        if(isset($spec->defaultView)){
            // create default view when defaultView is in model
            $defaultView = [
                "name" => $spec->defaultView->name,
                "label" => $spec->defaultView->label,
                "model" => $model->getName(),
                "attributes" => $spec->defaultView->attributes,
                "lensSimpleFilters" => self::generateLensFilter($spec->defaultView, $model, $registryManager)
            ];
        }else{
            // create default view from model
            $defaultView = [
                "name" => $model->getName()."_default_view",
                "label" => $model->getLabel()." Default View",
                "model" => $model->getName(),
                "attributes" => $model->getAttributes()->toArray(),
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
            if(count($path) === 2){
                $relatedModel = $registryManager->get('model', $path[0]);
                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => $relatedModel->getLabel()." ".ucfirst($path[1]),
                    'model' => $path[0]
                ];
            }elseif(count($path) === 1){
                $lensSimpleFilters[$lensSimpleFilter] = [
                    'type' => 'enum',
                    'label' => ucfirst($path[0]),
                    'model' => $model->getName()
                ];
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
