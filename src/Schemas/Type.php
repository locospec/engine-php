<?php

namespace LCSEngine\Schemas;

enum Type: string
{
    case MODEL = 'model';
    case QUERY = 'query';
    case MUTATOR = 'mutator';
}