<?php

namespace Locospec\Engine\Schema\Properties;

class NumberProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'number';
    }
}
