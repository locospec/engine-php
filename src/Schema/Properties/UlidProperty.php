<?php

namespace Locospec\LCS\Schema\Properties;

class UlidProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'ulid';
    }
}
