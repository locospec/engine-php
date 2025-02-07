<?php

namespace Locospec\Engine\Schema\Properties;

class StringProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'string';
    }
}
