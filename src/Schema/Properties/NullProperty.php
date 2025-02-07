<?php

namespace Locospec\Engine\Schema\Properties;

class NullProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'null';
    }
}
