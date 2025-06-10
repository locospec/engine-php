<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum OperationType: string
{
    case INSERT = 'insert';
    case UPDATE = 'update';
    case DELETE = 'delete';
} 