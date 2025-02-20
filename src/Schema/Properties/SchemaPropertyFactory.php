<?php

namespace Locospec\Engine\Schema\Properties;

use InvalidArgumentException;

class SchemaPropertyFactory
{
    private static array $typeMap = [
        'string' => StringProperty::class,
        'number' => NumberProperty::class,
        'integer' => IntegerProperty::class,
        'boolean' => BooleanProperty::class,
        'array' => ArrayProperty::class,
        'object' => ObjectProperty::class,
        'null' => NullProperty::class,
        'date' => DateProperty::class,
        'timestamp' => TimestampProperty::class,
        'ulid' => UlidProperty::class,
        'uuid' => UuidProperty::class,
    ];

    public static function create(string $type): SchemaPropertyInterface
    {
        if (! isset(self::$typeMap[$type])) {
            throw new InvalidArgumentException("Invalid property type: {$type}");
        }

        $className = self::$typeMap[$type];

        return new $className;
    }

    public static function registerType(string $type, string $className): void
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException("Class {$className} does not exist");
        }

        if (! is_subclass_of($className, SchemaPropertyInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s must implement SchemaPropertyInterface',
                htmlspecialchars($className, ENT_QUOTES, 'UTF-8')
            ));
        }

        self::$typeMap[$type] = $className;
    }
}
