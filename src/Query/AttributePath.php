<?php

namespace Locospec\LCS\Query;

class AttributePath
{
    /** @var string[] */
    private array $segments;

    private function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    public static function parse(string $path): self
    {
        // Split by both -> and . to handle both JSON paths and relationship paths
        $segments = preg_split('/(?:->|\.)/', $path);
        return new self(array_filter($segments));
    }

    public function isRelationshipPath(): bool
    {
        return count($this->segments) > 1;
    }

    public function getRelationshipPath(): ?string
    {
        if (!$this->isRelationshipPath()) {
            return null;
        }

        return implode('.', array_slice($this->segments, 0, -1));
    }

    public function getAttributeName(): string
    {
        return end($this->segments);
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function toString(): string
    {
        return implode('.', $this->segments);
    }

    public function getDepth(): int
    {
        return count($this->segments);
    }
}
