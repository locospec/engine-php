<?php

namespace LCSEngine\Schemas\DatabaseOperations;

enum PaginationType: string
{
    case OFFSET = 'offset';
    case CURSOR = 'cursor';
}
