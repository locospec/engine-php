<?php

namespace Locospec\Engine\Models\Traits;

use Locospec\Engine\Database\AliasExpressionParser;

trait HasAliases
{
    private array $aliases = [];

    private ?AliasExpressionParser $expressionParser = null;

    private function getExpressionParser(): AliasExpressionParser
    {
        if ($this->expressionParser === null) {
            $this->expressionParser = new AliasExpressionParser;
        }

        return $this->expressionParser;
    }

    public function addAlias(string $key, string|array $expression): void
    {
        if (is_string($expression)) {
            $this->aliases[$key] = $this->getExpressionParser()->parse($expression);
        } else {
            if (! isset($expression['extract'])) {
                throw new \InvalidArgumentException("Alias array must contain 'extract' key");
            }
            $this->aliases[$key] = $expression;
        }
    }

    public function getAlias(string $key): ?array
    {
        return $this->aliases[$key] ?? null;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    protected function loadAliasesFromArray(array $data): void
    {
        if (isset($data['aliases']) && is_array($data['aliases'])) {
            foreach ($data['aliases'] as $key => $expression) {
                $this->addAlias($key, $expression);
            }
        }
    }

    protected function aliasesToArray(): array
    {
        return ! empty($this->aliases) ? ['aliases' => $this->aliases] : [];
    }
}
