<?php

namespace Locospec\LCS\Schema\Properties;

class TimestampProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'timestamp';
    }
}
