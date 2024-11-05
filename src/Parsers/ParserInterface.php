<?php

namespace Locospec\EnginePhp\Parsers;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

interface ParserInterface
{
    public function parseJson(string $json): mixed;
    public function parseArray(array $data): mixed;
}
