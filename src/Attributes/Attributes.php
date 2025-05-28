<?php

namespace Locospec\Engine\Attributes;

class Attributes
{
    protected array $attributes = [];

    public function __construct()
    {
        $this->attributes = [];
    }

    /**
     * Adds an attribute instance to the collection.
     */
    public function addAttribute(AttributeInterface $attribute): void
    {
        $this->attributes[$attribute->getName()] = $attribute;
    }

    /**
     * Creates an Attributes instance from an object.
     *
     * Expects an object where each property represents an attribute definition,
     * for example:
     *
     * {
     *   "uuid": { "type": "uuid", "label": "ID" },
     *   "name": { "type": "string", "label": "Name" },
     *   ...
     * }
     */
    public static function fromObject(object $data): self
    {
        $instance = new self;
        foreach ($data as $name => $attrData) {
            // Ensure $attrData is an object
            $attrData = is_object($attrData) ? $attrData : (object) $attrData;
            $instance->addAttribute(Attribute::fromObject($name, $attrData));
        }

        return $instance;
    }

    /**
     * Retrieve an attribute by name.
     */
    public function getAttribute(string $name): ?AttributeInterface
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Retrieve attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve attributes for the given names.
     *
     * @param  string[]  $names  Array of attribute names.
     * @return AttributeInterface[] Array of attribute objects corresponding to the given names.
     */
    public function getAttributesByNames(array $names): array
    {
        // return $this->attributes;
        $result = [];
        foreach ($names as $name) {
            $attribute = $this->getAttribute($name);
            if ($attribute !== null) {
                $result[$name] = $attribute->toArray();
            }
        }

        return $result;
    }

    /**
     * Retrieve all attribute names.
     *
     * @return string[]
     */
    public function getAllAttributeNames(): array
    {
        return array_keys($this->attributes);
    }

    /**
     * Return all attributes.
     *
     * @return AttributeInterface[]
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Converts the attributes collection to an associative array.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->attributes as $name => $attribute) {
            $result[$name] = $attribute->toArray();
        }

        return $result;
    }

    /**
     * Converts the attributes collection to an object.
     */
    public function toObject(): object
    {
        return json_decode(json_encode($this->toArray()));
    }
}
