<?php

namespace Locospec\EnginePhp\Registry;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class ModelRegistry extends AbstractRegistry
{
    public function getType(): string
    {
        return 'model';
    }

    protected function getItemName(mixed $item): string
    {
        return $item->getName();
    }
}
