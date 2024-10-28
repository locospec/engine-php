<?php

namespace Locospec\EnginePhp\Schema\Properties;

class NullProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'null';
    }
}
