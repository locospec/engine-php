<?php

namespace Locospec\EnginePhp\Schema\Properties;

class NumberProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'number';
    }
}
