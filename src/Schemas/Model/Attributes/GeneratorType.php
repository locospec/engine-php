<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum GeneratorType: string
{
    case UUID_GENERATOR = 'uuid_generator';
    case SLUG_GENERATOR = 'slug_generator';
    case TIMESTAMP_GENERATOR = 'timestamp_generator';
} 