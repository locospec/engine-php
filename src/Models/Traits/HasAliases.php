<?php

namespace Locospec\Engine\Models\Traits;

use Locospec\Engine\Database\AliasExpressionParser;

trait HasAliases
{
    private object $aliases;

    private ?AliasExpressionParser $expressionParser = null;

    private function getExpressionParser(): AliasExpressionParser
    {
        if ($this->expressionParser === null) {
            $this->expressionParser = new AliasExpressionParser;
        }

        return $this->expressionParser;
    }

    public function addAlias(string $key, string|object $expression): void
    {
        if (is_string($expression)) {
            $this->aliases->$key = $this->getExpressionParser()->parse($expression);
        } else {
            if (! isset($expression->extract)) {
                throw new \InvalidArgumentException("Alias array must contain 'extract' key");
            }

            $this->aliases->$key = $expression;
        }
    }

    public function getAlias(string $key): ?object
    {
        return $this->aliases->$key ?? null;
    }

    public function getAliases(): object
    {
        return $this->aliases;
    }

    protected function loadAliasesFromArray(object $data): void
    {
        if (! isset($this->aliases)) {
            $this->aliases = new \stdClass; // Initialize aliases as an object
        }

        if (isset($data->aliases)) {
            foreach ($data->aliases as $key => $expression) {
                $this->addAlias($key, $expression);
            }
        }
    }

    protected function aliasesToArray(): array
    {
        return ! empty($this->aliases) ? ['aliases' => $this->aliases] : [];
    }
}
