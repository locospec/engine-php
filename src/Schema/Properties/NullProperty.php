<?php

namespace Locospec\LCS\Schema\Properties;

class NullProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'null';
    }
}
