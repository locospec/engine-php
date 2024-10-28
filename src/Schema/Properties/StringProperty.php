<?php

namespace Locospec\EnginePhp\Schema\Properties;

class StringProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'string';
    }
}
