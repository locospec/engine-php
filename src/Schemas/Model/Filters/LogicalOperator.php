<?php

namespace LCSEngine\Schemas\Model\Filters;

enum LogicalOperator: string
{
    case AND = 'and';
    case OR = 'or';
} 