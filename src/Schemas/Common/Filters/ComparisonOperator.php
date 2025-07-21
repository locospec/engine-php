<?php

namespace LCSEngine\Schemas\Common\Filters;

enum ComparisonOperator: string
{
    case IS = 'is';
    case IS_NOT = 'is_not';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN = 'less_than';
    case GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    case LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case IS_ANY_OF = 'is_any_of';
    case IS_NONE_OF = 'is_none_of';
    case IS_EMPTY = 'is_empty';
    case IS_NOT_EMPTY = 'is_not_empty';
}
