<?php

namespace LCSEngine\Schemas\Mutator;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Model;
use LCSEngine\Schemas\Type;

class Mutator
{
    private string $name;

    private string $label;

    private Type $type;

    private DbOpType $dbOp;

    private string $model;

    private Collection $attributes;

    private ?UISchema $uiSchema = null;

    private ?Schema $schema = null;

    public function __construct(string $name, string $label, DbOpType $dbOp, string $model)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = Type::MUTATOR;
        $this->dbOp = $dbOp;
        $this->model = $model;
        $this->attributes = collect();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getDbOp(): DbOpType
    {
        return $this->dbOp;
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function hasAttribute(string $attributeName): bool
    {
        return $this->attributes->has($attributeName);
    }

    public function getAttribute(string $attributeName): ?Attribute
    {
        return $this->attributes->get($attributeName);
    }

    public function addAttribute(Attribute $attribute): void
    {
        $this->attributes->put($attribute->getName(), $attribute);
    }

    public function removeAttribute(string $attributeName): void
    {
        $this->attributes->forget($attributeName);
    }

    public function setSchema(Schema $schema): void
    {
        $this->schema = $schema;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function setUISchema(UISchema $uiSchema): void
    {
        $this->uiSchema = $uiSchema;
    }

    public function getUISchema(): ?UISchema
    {
        return $this->uiSchema;
    }

    public static function fromArray(array $data, Model $model): self
    {
        $mutator = new self(
            $data['name'],
            $data['label'],
            DbOpType::from($data['dbOp']),
            $data['model']
        );

        if (! empty($data['attributes']) && is_array($data['attributes'])) {
            $validAttributeKeys = $model->getAttributes()->filter(fn ($a) => ! $a->isAliasKey())->keys()->all();
            foreach ($data['attributes'] as $key => $attributeData) {
                if (! in_array($key, $validAttributeKeys)) {
                    throw new InvalidArgumentException(
                        "Attribute {$key} doesn't exists in the model {$model->getName()}."
                    );
                }

                $mutator->addAttribute(Attribute::fromArray($key, $attributeData));
            }
        } else {
            // Get all attributes from the model
            $model->getAttributes()->each(function ($attribute) use ($mutator) {
                if (! $attribute->isAliasKey()) {
                    $mutator->addAttribute($attribute);
                }
            });
        }

        // Add primary key attribute if not already present
        $primaryKeyAttribute = $model->getPrimaryKey();

        if ($primaryKeyAttribute && ! $mutator->hasAttribute($primaryKeyAttribute->getName())) {
            $mutator->addAttribute($primaryKeyAttribute);
        }

        // Generate schema from attributes
        $mutator->setSchema(Schema::fromAttributes($mutator->getAttributes()));

        // Set UI schema if present
        if (! empty($data['uiSchema'])) {
            $mutator->setUISchema(UISchema::fromArray($data['uiSchema']));
        }

        return $mutator;
    }

    public function toArray(): array
    {
        $arr = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'dbOp' => $this->dbOp->value,
            'model' => $this->model,
        ];

        if (! $this->attributes->isEmpty()) {
            $arr['attributes'] = $this->attributes->map(fn ($a) => $a->toArray())->all();
        }

        if ($this->schema) {
            $arr['schema'] = $this->schema->toArray();
        }

        if ($this->uiSchema) {
            $arr['uiSchema'] = $this->uiSchema->toArray();
        }

        return $arr;
    }

    public static function fromModel(Model $model, DbOpType $dbOp): self
    {
        $actionValue = $dbOp->value;

        $name = $model->getName()."_default_{$actionValue}_mutator";
        $label = $model->getLabel().' Default Create Mutator';

        $spec = [
            'name' => $name,
            'label' => $label,
            'dbOp' => $dbOp->value,
            'model' => $model->getName(),
        ];

        $mutator = self::fromArray($spec, $model);

        return $mutator;
    }
}
