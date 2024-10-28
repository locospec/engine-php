<?php

namespace Locospec\EnginePhp\Schema\Properties;

class IntegerProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'integer';
    }
}
