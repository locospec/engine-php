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
    
    private array $schema = [];
    
    private array $uiSchema = [];

    private array $attributes = [];

    public function __construct(string $name, string $label, string $dbOp, string $modelName, array $attributes, array $schema, array $uiSchema)
    {
        $this->type = 'action';
        $this->name = $name;
        $this->label = $label;
        $this->model = $modelName;
        $this->dbOp = $dbOp;
        $this->attributes = $attributes;
        $this->schema = $schema;
        $this->uiSchema = $uiSchema;
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
 
    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getUiSchema(): array
    {
        return $this->uiSchema;
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

        $schema = isset($data->schema) ? (array) $data->schema : self::generateSchema($attributes);
        $uiSchema = isset($data->uiSchema) ? (array) $data->uiSchema : self::generateUiSchema($attributes);

        return new self($data->name, $data->label, $data->dbOp, $data->model, $attributes, $schema, $uiSchema);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'model' => $this->model,
            'attributes' => $this->attributes,
            'schema' => $this->schema,
            'uiSchema' => $this->uiSchema,
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
        $result->schema = $this->schema;
        $result->uiSchema = $this->uiSchema;

        return $result;
    }

     /**
     * Generates JSON schema from attributes
     * @param object $attributes
     * @return array
     */
    public static function generateSchema(array $attributes): array
    {
        $properties = [];
        $required = [];

        foreach ($attributes as $fieldName => $fieldConfig) {
            $property = [
                'type' => $fieldConfig['type'],
                'description' => $fieldConfig['label'] ?? ucfirst($fieldName)
            ];

            // Add the property to schema
            $properties[$fieldName] = $property;

            // Check validations
            if (isset($fieldConfig['validations'])) {
                foreach ($fieldConfig['validations'] as $validation) {
                    // Handle required validation
                    if ($validation->type === 'required') {
                        $required[] = $fieldName;
                    }

                    // Handle regex pattern
                    if (str_starts_with($validation->type, 'regex:')) {
                        $pattern = substr($validation->type, 6); // Remove 'regex:' prefix
                        $property['pattern'] = trim($pattern, '/'); // Remove regex delimiters
                    }

                    // Add error message if provided
                    if (isset($validation->message)) {
                        $property['errorMessage'] = $validation->message;
                    }
                }
            }

            $properties[$fieldName] = $property;
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required
        ];
    }

    /**
     * Generates UI schema from attributes
     * @param object $attributes
     * @return array
     */
    public static function generateUiSchema(array $attributes): array
    {
        $elements = [];

        foreach ($attributes as $fieldName => $fieldConfig) {
            $element = [
                'type' => 'Control',
                'scope' => "#/properties/{$fieldName}"
            ];

            // Add label if provided
            if (isset($fieldConfig['label'])) {
                $element['label'] = $fieldConfig['label'];
            }

            $elements[] = $element;
        }

        return [
            'type' => 'VerticalLayout',
            'elements' => $elements
        ];
    }
}
