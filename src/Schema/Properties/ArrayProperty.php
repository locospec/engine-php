<?php

namespace Locospec\EnginePhp\Schema\Properties;

class ArrayProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    use HasSchemaTrait;

    public function getType(): string
    {
        return 'array';
    }
}
