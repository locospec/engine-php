<?php

namespace Locospec\LCS\Database\Relationships;

use Locospec\LCS\Models\ModelDefinition;

class RelationshipPath
{
    private array $relationships = [];
    private string $attribute;
    private string $originalPath;

    private function __construct(string $path, ModelDefinition $startingModel)
    {
        $this->originalPath = $path;
        $segments = explode('.', $path);

        $this->attribute = array_pop($segments);

        if (!empty($segments)) {
            $target = array_pop($segments);
            $this->relationships[] = [
                'source' => $startingModel->getName(),
                'target' => $target
            ];

            for ($i = 0; $i < count($segments) - 1; $i++) {
                $this->relationships[] = [
                    'source' => $segments[$i],
                    'target' => $segments[$i + 1]
                ];
            }
        }
    }

    public static function parse(string $path, ModelDefinition $startingModel): self
    {
        return new self($path, $startingModel);
    }

    public function isRelationshipPath(): bool
    {
        return !empty($this->relationships);
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }
}
