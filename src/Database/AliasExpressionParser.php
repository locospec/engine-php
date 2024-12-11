<?php

namespace Locospec\LCS\Database;

class AliasExpressionParser
{
    public function parse(string $expression): array
    {
        // Remove any leading/trailing whitespace
        $expression = trim($expression);

        // If there's no pipe, it's just extraction
        if (!str_contains($expression, '|')) {
            return [
                'extract' => $expression,
                'transform' => null
            ];
        }

        // Split on first pipe to separate extract and transform
        $parts = explode('|', $expression, 2);

        return [
            'extract' => trim($parts[0]),
            'transform' => trim($parts[1])
        ];
    }
}
