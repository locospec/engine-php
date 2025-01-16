<?php

namespace Locospec\Engine\Schema\Properties;

class TimestampProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'timestamp';
    }
}
