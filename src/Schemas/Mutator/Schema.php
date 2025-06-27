<?php

namespace LCSEngine\Schemas\Mutator;

use Illuminate\Support\Collection;
use LCSEngine\Schemas\Model\Attributes\Attribute;
use LCSEngine\Schemas\Model\Attributes\Type as AttributeType;

class Schema
{
    private Collection $properties;

    private array $required;

    public function __construct()
    {
        $this->properties = collect();
        $this->required = [];
    }

    public function addProperty(Attribute $attribute): void
    {
        $property = [
            'type' => $this->getJsonSchemaType($attribute->getType()),
        ];

        // if ($attribute->getIsAlias()) {
        //     if ($attribute->hasAliasSource()) {
        //         $property['source'] = $attribute->getAliasSource();
        //     }
        //     if ($attribute->hasAliasTransformation()) {
        //         $property['transform'] = $attribute->getAliasTransformation();
        //     }
        // }

        if (in_array($attribute->getType(), [AttributeType::DATE, AttributeType::TIMESTAMP])) {
            $property['format'] = 'date-time';
        }

        if ($attribute->getRelatedModelName()) {
            $property['relatedModelName'] = $attribute->getRelatedModelName();
        }

        if (! $attribute->getDependsOn()->isEmpty()) {
            $property['dependsOn'] = $attribute->getDependsOn()->all();
        }

        if (! $attribute->getOptions()->isEmpty()) {
            $property['options'] = $attribute->getOptions()->map(fn ($option) => $option->toArray())->all();
        }

        $this->properties->put($attribute->getName(), $property);

        if ($attribute->getValidators()->contains(fn ($validator) => $validator->getType()->value === 'required')) {
            $this->required[] = $attribute->getName();
        }
    }

    private function getJsonSchemaType(AttributeType $type): string
    {
        return match ($type) {
            AttributeType::STRING, AttributeType::TEXT, AttributeType::UUID, AttributeType::ULID => 'string',
            AttributeType::INTEGER, AttributeType::ID, AttributeType::DECIMAL => 'number',
            AttributeType::BOOLEAN => 'boolean',
            AttributeType::DATE, AttributeType::TIMESTAMP => 'string',
            AttributeType::JSON, AttributeType::JSONB, AttributeType::OBJECT => 'object',
            default => 'string'
        };
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $this->properties->all(),
        ];

        if (! empty($this->required)) {
            $schema['required'] = $this->required;
        }

        return $schema;
    }

    public static function fromAttributes(Collection $attributes): self
    {
        $schema = new self;
        $attributes->each(fn ($attribute) => $schema->addProperty($attribute));

        return $schema;
    }
}
