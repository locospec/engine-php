<?php

namespace Locospec\Engine\Database;

use JmesPath\AstRuntime;
use JmesPath\FnDispatcher;
use DateTime;

class JMESPathCustomRuntime
{
    protected AstRuntime $runtime;

    public function __construct()
    {
        $defaultDispatcher = new FnDispatcher();
        $customDispatcher = function ($fn, array $args) use ($defaultDispatcher) {
            switch ($fn) {
                case 'format_date':
                    return $this->handleFormatDate($args);

                default:
                    return $defaultDispatcher($fn, $args);
            }
        };

        $this->runtime = new AstRuntime(null, $customDispatcher);
    }

    public function search(string $expression, $data)
    {
        return ($this->runtime)($expression, $data); 
    }

    protected function handleFormatDate(array $args)
    {
        if (count($args) !== 3) return null;

        [$dateStr, $fromFormat, $toFormat] = $args;

        $date = DateTime::createFromFormat($fromFormat, $dateStr);
        return $date ? $date->format($toFormat) : null;
    }
}
