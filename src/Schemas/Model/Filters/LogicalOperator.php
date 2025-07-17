<?php

namespace LCSEngine\Schemas\Model\Filters;

enum LogicalOperator: string
{
    case AND = 'and';
    case OR = 'or';
    case BATCHED_AND = 'batched-and';
    case BATCHED_OR = 'batched-or';
}
