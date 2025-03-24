<?php

namespace Locospec\Engine\Actions;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Locospec\Engine\Models\ModelDefinition;
use Locospec\Engine\Registry\RegistryManager;

class ActionDefinition
{
    private string $type;

    private string $name;

    private string $label;

    private string $dbOp;

    private string $model;

    private array $attributes = [];

    public function __construct(string $name, string $label, string $dbOp, string $modelName, array $attributes)
    {
        $this->type = 'action';
        $this->name = $name;
        $this->label = $label;
        $this->model = $modelName;
        $this->dbOp = $dbOp;
        $this->attributes = $attributes;
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

    public function getDbOp(): string
    {
        return $this->dbOp;
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
        $attributes = [];

        $validAttributeKeys = array_keys($model->getAttributes()->getAttributes());
        foreach ($data->attributes as $key => $attribute) {
            if (! in_array($key, $validAttributeKeys)) {
                throw new InvalidArgumentException(
                    "Attribute doesn't exists in the model: Model {$model->getName()} not found"
                );
            } else {
                $attributes[$key] = (array) $attribute;
            }
        }

        return new self($data->name, $data->label, $data->dbOp, $data->model, $attributes);
    }

    public static function fromArray(array $data): self
    {
        ViewValidator::validate($data);

        return new self($data['name'], $data['label'], $data['dbOp'], $data['model'], $data['attributes']);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'model' => $this->model,
            'attributes' => $this->attributes,
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

        return $result;
    }
}
