<?php

namespace Locospec\LCS\Schema\Properties;

class ObjectProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    use HasSchemaTrait;

    public function getType(): string
    {
        return 'object';
    }
}
