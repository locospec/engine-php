<?php

namespace LCSEngine\Schemas\Mutator;

enum DbOpType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
