<?php

namespace Locospec\EnginePhp\Schema;

use Locospec\EnginePhp\Schema\Properties\SchemaPropertyInterface;
use Locospec\EnginePhp\Schema\Properties\SchemaPropertyFactory;

class Schema
{
    private array $properties = [];
    private ?string $title = null;
    private ?string $description = null;

    public function __construct(?string $title = null, ?string $description = null)
    {
        $this->title = $title;
        $this->description = $description;
    }

    public function addProperty(string $name, SchemaPropertyInterface $property): self
    {
        $this->properties[$name] = $property;
        return $this;
    }

    public function getProperty(string $name): ?SchemaPropertyInterface
    {
        return $this->properties[$name] ?? null;
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->title) {
            $result['title'] = $this->title;
        }

        if ($this->description) {
            $result['description'] = $this->description;
        }

        foreach ($this->properties as $name => $property) {
            $result[$name] = $property->toArray();
        }

        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public static function fromArray(array $data): self
    {
        $schema = new self();

        foreach ($data as $propertyName => $propertyData) {
            if (is_string($propertyData)) {
                // Simple type definition
                $property = SchemaPropertyFactory::create($propertyData);
                $schema->addProperty($propertyName, $property);
            } elseif (is_array($propertyData)) {
                // Complex type definition
                $type = $propertyData['type'] ?? 'object';
                $property = SchemaPropertyFactory::create($type);

                if (isset($propertyData['schema'])) {
                    if (method_exists($property, 'setSchema')) {
                        $nestedSchema = self::fromArray($propertyData['schema']);
                        $property->setSchema($nestedSchema);
                    }
                }

                $schema->addProperty($propertyName, $property);
            }
        }

        return $schema;
    }

    public function toShortArray(): array
    {
        $fullArray = $this->toArray();
        return $this->convertToShortFormat($fullArray);
    }

    private function convertToShortFormat(array $schema): array
    {
        $result = [];

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                // Handle simple type definition
                if (isset($value['type']) && count($value) === 1) {
                    $result[$key] = $value['type'];
                    continue;
                }

                // Handle object/array with schema
                if (isset($value['type']) && isset($value['schema'])) {
                    $result[$key] = [
                        'type' => $value['type'],
                        'schema' => $this->convertToShortFormat($value['schema'])
                    ];
                    continue;
                }

                // Recursively convert nested arrays
                $result[$key] = $this->convertToShortFormat($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
