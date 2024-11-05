<?php

namespace Locospec\EnginePhp\Parsers;

interface ParserInterface
{
    public function parseJson(string $json): mixed;

    public function parseArray(array $data): mixed;
}
