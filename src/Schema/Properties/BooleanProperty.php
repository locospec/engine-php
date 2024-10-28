<?php

namespace Locospec\EnginePhp\Schema\Properties;

class BooleanProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'boolean';
    }
}
