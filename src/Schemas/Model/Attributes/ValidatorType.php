<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum ValidatorType: string
{
    case REQUIRED = 'required';
    case UNIQUE = 'unique';
}
