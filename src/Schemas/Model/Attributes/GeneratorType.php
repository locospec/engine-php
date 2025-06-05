<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum GeneratorType: string
{
    case UUID = 'uuid';
    case UNIQUE_SLUG = 'unique_slug';
    case DATETIME = 'datetime';
    case STATE_MACHINE = 'state_machine';
    case TIMESTAMP_GENERATOR = 'timestamp_generator';
}
