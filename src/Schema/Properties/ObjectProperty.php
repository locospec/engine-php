<?php

namespace Locospec\Engine\Schema\Properties;

class ObjectProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    use HasSchemaTrait;

    public function getType(): string
    {
        return 'object';
    }
}
