<?php

namespace LCSEngine\Schemas\Mutator;

enum LayoutType: string
{
    case VERTICAL_LAYOUT = 'VerticalLayout';
    case HORIZONTAL_LAYOUT = 'HorizontalLayout';
    case GROUP = 'Group';
}
