<?php

namespace Locospec\EnginePhp\Schema\Properties;

class UuidProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'uuid';
    }
}
