<?php

namespace Locospec\LCS\Database\Relationships;

class RelationshipPath
{
    private array $segments;

    private string $originalPath;

    private function __construct(string $path)
    {
        $this->originalPath = $path;
        $this->segments = explode('.', $path);
    }

    public static function parse(string $path): self
    {
        return new self($path);
    }

    public function isRelationshipPath(): bool
    {
        return count($this->segments) > 1;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }
}
