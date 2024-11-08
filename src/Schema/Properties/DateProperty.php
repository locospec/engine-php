<?php

namespace Locospec\LCS\Schema\Properties;

class DateProperty extends AbstractSchemaProperty implements SchemaPropertyInterface
{
    public function getType(): string
    {
        return 'date';
    }
}
