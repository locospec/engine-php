<?php

namespace LCSEngine\Schemas\Model\Attributes;

enum AttributeType: string
{
    case UUID = 'uuid';
    case ULID = 'ulid';
    case ALIAS = 'alias';
    case STRING = 'string';
    case TIMESTAMP = 'timestamp';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DECIMAL = 'decimal';
    case JSON = 'json';
    case JSONB = 'jsonb';
    case OBJECT = 'object';
} 