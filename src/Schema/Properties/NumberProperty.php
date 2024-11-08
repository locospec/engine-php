<?php

namespace Locospec\LCS\Schema\Properties;

class NumberProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'number';
    }
}
