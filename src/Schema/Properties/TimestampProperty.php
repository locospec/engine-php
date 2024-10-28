<?php

namespace Locospec\EnginePhp\Schema\Properties;

class TimestampProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'timestamp';
    }
}
