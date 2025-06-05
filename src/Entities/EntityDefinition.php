<?php

namespace LCSEngine\Entities;

use LCSEngine\Exceptions\InvalidArgumentException;
use LCSEngine\Registry\RegistryManager;
use LCSEngine\Schemas\Model\Model;

class EntityDefinition
{
    private string $type;

    private string $name;

    private string $label;

    private string $model;

    private array $layout = [];

    private array $expand = [];

    public function __construct(string $name, string $label, string $modelName, array $layout, array $expand)
    {
        $this->type = 'entity';
        $this->name = $name;
        $this->label = $label;
        $this->model = $modelName;
        $this->layout = $layout;
        $this->expand = $expand;
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

    public function getLayout(): array
    {
        return $this->layout;
    }

    public static function fromObject(object $data, RegistryManager $registryManager, Model $model): self
    {
        $attributes = [];
        $expand = [];

        EntityValidator::validate($data);

        $fullLayout = self::generateFullLayout($model, $data->layout);

        if (isset($data->expand)) {
            $expand = $data->expand;
        }

        return new self($data->name, $data->label, $data->model, $fullLayout, $expand);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'model' => $this->model,
            'layout' => $this->layout,
            'expand' => $this->expand,
        ];
    }

    public function toObject(): object
    {
        $result = new \stdClass;
        $result->name = $this->name;
        $result->label = $this->label;
        $result->type = $this->type;
        $result->model = $this->model;
        $result->layout = $this->layout;
        $result->expand = $this->expand;

        return $result;
    }

    /**
     * Generates layout
     *
     * @param  object  $attributes
     */
    public static function generateFullLayout(Model $model, array $shorthandLayout): array
    {
        $attributes = $model->getAttributes()->map(fn($attribute) => $attribute->toArray())->all();
        $transformed = self::transformLayout($shorthandLayout, $attributes);

        return $transformed;
    }

    /**
     * Transforms a shorthand layout into a full layout specification
     *
     * @param  array  $shorthandLayout  The shorthand layout
     * @param  array  $attributes  The model attributes for field metadata
     * @return array The transformed full layout
     *
     * @throws InvalidArgumentException If the layout is invalid
     */
    private static function transformLayout(array $shorthandLayout, array $attributes): array
    {
        $layout = $shorthandLayout['layout'] ?? $shorthandLayout;

        // Check if the layout is a flat array of field keys
        $isFlatArray = true;
        foreach ($layout as $item) {
            if (is_array($item)) {
                $isFlatArray = false;
                break;
            }
        }

        if ($isFlatArray) {
            // Handle flat array format (Example 2)
            return [[
                'fields' => self::processFields($layout, $attributes),
            ]];
        }

        // Handle sectioned layout (with or without section headers)
        return self::processLayout($layout, $attributes);
    }

    /**
     * Processes the layout array, handling sections (named or unnamed) and nested structures
     *
     * @param  array  $layout  The layout array
     * @param  array  $attributes  The model attributes
     * @return array Processed layout
     *
     * @throws InvalidArgumentException
     */
    private static function processLayout(array $layout, array $attributes): array
    {
        $result = [];

        foreach ($layout as $section) {
            if (! is_array($section) || empty($section)) {
                throw new InvalidArgumentException('Invalid section: Each section must be a non-empty array');
            }

            $sectionData = ['fields' => []];
            $offset = 0;

            // Check if the first element is a section header
            if (is_string($section[0]) && str_starts_with($section[0], '$')) {
                $sectionData['section'] = substr($section[0], 1); // Remove '$' prefix
                $offset = 1;
            }

            // Process remaining elements in the section
            for ($i = $offset; $i < count($section); $i++) {
                $item = $section[$i];

                if (is_array($item)) {
                    if (! empty($item) && is_string($item[0]) && str_starts_with($item[0], '$')) {
                        // Nested section
                        $nestedSectionName = substr($item[0], 1);
                        $sectionData['fields'][] = [
                            'section' => $nestedSectionName,
                            'fields' => self::processFields(array_slice($item, 1), $attributes),
                        ];
                    } else {
                        // Column (array of fields)
                        $sectionData['fields'][] = [
                            'fields' => self::processFields($item, $attributes),
                        ];
                    }
                } else {
                    // Single field
                    $sectionData['fields'][] = self::expandField($item, $attributes);
                }
            }

            $result[] = $sectionData;
        }

        return $result;
    }

    /**
     * Processes an array of fields, handling both simple fields and list fields
     *
     * @param  array  $fields  The fields array
     * @param  array  $attributes  The model attributes
     * @return array Processed fields
     *
     * @throws InvalidArgumentException
     */
    private static function processFields(array $fields, array $attributes): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                // Handle list fields like ["images[*].url", "images[*].caption"]
                $result[] = self::expandListField($field, $attributes);
            } else {
                // Handle simple field
                $result[] = self::expandField($field, $attributes);
            }
        }

        return $result;
    }

    /**
     * Expands a single field key into a full field object
     *
     * @param  string  $key  The field key
     * @param  array  $attributes  The model attributes
     * @return array The expanded field object
     *
     * @throws InvalidArgumentException
     */
    private static function expandField(string $key, array $attributes): array
    {
        // if (!isset($attributes[$key])) {
        //     throw new InvalidArgumentException("Field '$key' not found in model attributes");
        // }

        // $attr = $attributes[$key];
        // $field = [
        //     'key' => $key,
        //     'label' => $attr->label ?? self::generateLabel($key),
        //     'type' => $attr->type ?? 'string'
        // ];
        $field = [
            'key' => $key,
            'label' => self::generateLabel($key),
            'type' => 'string',
        ];

        // Add optional properties if present
        // if (isset($attr->display)) {
        //     $field['display'] = $attr['display'];
        // }

        return $field;
    }

    /**
     * Expands list fields (e.g., ["images[*].url", "images[*].caption"])
     *
     * @param  array  $fieldKeys  The list field keys
     * @param  array  $attributes  The model attributes
     * @return array The expanded list field object
     *
     * @throws InvalidArgumentException
     */
    private static function expandListField(array $fieldKeys, array $attributes): array
    {
        $baseKey = null;
        $subFields = [];

        foreach ($fieldKeys as $key) {
            if (! preg_match('/^(.+)\[\*\]\.(.+)$/', $key, $matches)) {
                throw new InvalidArgumentException("Invalid list field syntax: '$key'. Expected format: 'field[*].subfield'");
            }

            $baseKey = $matches[1];
            $subKey = $matches[2];

            // if (! isset($attributes[$baseKey]) || ! isset($attributes[$baseKey]['fields'][$subKey])) {
            //     throw new InvalidArgumentException("List field '$key' not found in model attributes");
            // }

            // $subAttr = $attributes[$baseKey]['fields'][$subKey];
            // $subField = [
            //     'key' => $subKey,
            //     'label' => $subAttr['label'] ?? self::generateLabel($subKey),
            //     'type' => $subAttr['type'] ?? 'string',
            // ];

            $subField = [
                'key' => $subKey,
                'label' => self::generateLabel($subKey),
                'type' => 'string',
            ];

            if (isset($subAttr['display'])) {
                $subField['display'] = $subAttr['display'];
            }

            $subFields[] = $subField;
        }

        if (! $baseKey) {
            throw new InvalidArgumentException('No valid list fields provided');
        }

        $attr = $attributes[$baseKey] ?? [];

        return [
            'key' => $baseKey,
            // 'label' => $attr['label'] ?? self::generateLabel($baseKey),
            'label' => self::generateLabel($baseKey),
            'type' => 'list',
            'fields' => $subFields,
        ];
    }

    /**
     * Generates a human-readable label from a field key
     *
     * @param  string  $key  The field key
     * @return string The generated label
     */
    private static function generateLabel(string $key): string
    {
        return str_replace('_', ' ', ucwords($key));
    }
}
