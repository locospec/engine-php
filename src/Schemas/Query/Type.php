<?php

namespace LCS\Engine\Schemas\Query;

enum Type: string
{
    case MODEL = 'model';
    case QUERY = 'query';
    case MUTATOR = 'mutator';
} 