<?php

namespace Locospec\Engine\Schema\Properties;

class UlidProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'ulid';
    }
}
