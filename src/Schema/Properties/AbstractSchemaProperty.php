<?php

namespace Locospec\EnginePhp\Schema\Properties;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Schema\Schema;

abstract class AbstractSchemaProperty implements SchemaPropertyInterface
{
    protected ?Schema $schema = null;

    abstract public function getType(): string;

    public function toArray(): array
    {
        $result = ['type' => $this->getType()];

        if ($this->schema) {
            $result['schema'] = $this->schema->toArray();
        }

        return $result;
    }

    public function setSchema(Schema $schema): self
    {
        throw new InvalidArgumentException('Schema can only be set for object or array types');
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }
}
