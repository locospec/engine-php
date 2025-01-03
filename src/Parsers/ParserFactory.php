<?php

namespace Locospec\LCS\Parsers;

use Locospec\LCS\Exceptions\InvalidArgumentException;

class ParserFactory
{
    private array $parsers = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    private function registerDefaults(): void
    {
        $this->registerParser('model', new ModelParser);
        $this->registerParser('view', new ViewParser);
    }

    public function registerParser(string $type, ParserInterface $parser): void
    {
        $this->parsers[$type] = $parser;
    }

    public function createParser(string $type): ParserInterface
    {
        if (! isset($this->parsers[$type])) {
            throw new InvalidArgumentException("No parser registered for type: {$type}");
        }

        return $this->parsers[$type];
    }
}
