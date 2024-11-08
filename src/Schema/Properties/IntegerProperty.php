<?php

namespace Locospec\LCS\Schema\Properties;

class IntegerProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'integer';
    }
}
