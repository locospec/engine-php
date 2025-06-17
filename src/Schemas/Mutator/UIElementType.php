<?php

namespace LCSEngine\Schemas\Mutator;

enum UIElementType: string
{
    case CONTROL = 'Control';
    case GROUP = 'Group';
    case VERTICAL_LAYOUT = 'VerticalLayout';
    case HORIZONTAL_LAYOUT = 'HorizontalLayout';
    case ENUM = 'ENUM';
    case LENS_ENUM = 'lens-enum';
    case LENS_SWITCH = 'lens-switch';
    case LENS_TEXT_INPUT = 'lens-text-input';
    case LENS_DROPDOWN = 'lens-dropdown';
    case LENS_CALENDAR = 'lens-calendar';
    case LENS_CALENDAR_DATE_TIME = 'lens-calendar-date-time';
}
