<?php

namespace LCS\Engine\Schemas\Query;

enum SelectionType: string
{
    case NONE = 'none';
    case SINGLE = 'single';
    case MULTIPLE = 'multiple';
}
