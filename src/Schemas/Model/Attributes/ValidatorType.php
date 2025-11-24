<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum ValidatorType: string
{
    case REQUIRED = 'required';
    case UNIQUE = 'unique';
    case EXISTS = 'exists';
    case EMAIL = 'email';
    case REGEX = 'regex';
}
