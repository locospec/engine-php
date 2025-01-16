<?php

namespace Locospec\Engine\Schema\Properties;

class UuidProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'uuid';
    }
}
