<?php

namespace Locospec\EnginePhp\Schema\Properties;

class DateProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'date';
    }
}
