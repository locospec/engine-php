<?php

namespace Locospec\Engine\Actions\Model;

/**
 * Standard Create action for models
 */
class CustomAction extends ModelAction
{
    public static function getName(): string
    {
        return '_create';
    }

    protected function getStateMachineDefinition(array $def = []): array
    {
        try {
            return $def;
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
