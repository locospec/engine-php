<?php

namespace Locospec\Engine\Schema\Properties;

class DateProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'date';
    }
}
