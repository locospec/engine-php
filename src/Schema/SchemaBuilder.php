<?php

namespace Locospec\Engine\Schema;

use Locospec\Engine\Schema\Properties\SchemaPropertyFactory;
use Locospec\Engine\Schema\Properties\SchemaPropertyInterface;

class SchemaBuilder
{
    private Schema $schema;

    public function __construct()
    {
        $this->schema = new Schema;
    }

    public function string(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('string');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function number(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('number');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function integer(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('integer');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function boolean(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('boolean');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function array(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('array');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function object(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('object');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function date(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('date');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function timestamp(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('timestamp');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function ulid(string $name): SchemaPropertyInterface
    {
        $property = SchemaPropertyFactory::create('ulid');
        $this->schema->addProperty($name, $property);

        return $property;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
