<?php

namespace LCSEngine\Schemas\Model\Relationships;

enum Type: string
{
    case HAS_ONE = 'has_one';
    case BELONGS_TO = 'belongs_to';
    case HAS_MANY = 'has_many';
}
