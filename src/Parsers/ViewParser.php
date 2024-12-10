<?php

namespace Locospec\LCS\Parsers;

use Locospec\LCS\Exceptions\InvalidArgumentException;
use Locospec\LCS\Views\ViewDefinition;

class ViewParser implements ParserInterface
{
    public function parseJson(string $json): mixed
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided');
        }

        return $this->parseArray($data);
    }

    public function parseArray(array $data): mixed
    {
        return ViewDefinition::fromArray($data);
    }
}
