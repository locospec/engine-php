<?php

namespace Locospec\LCS\Schema\Properties;

class UuidProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'uuid';
    }
}
