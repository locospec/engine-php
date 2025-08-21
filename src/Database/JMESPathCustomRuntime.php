<?php

namespace LCSEngine\Database;

use DateTime;
use JmesPath\AstRuntime;
use JmesPath\FnDispatcher;

class JMESPathCustomRuntime
{
    protected AstRuntime $runtime;

    public function __construct()
    {
        $defaultDispatcher = new FnDispatcher;
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
        $count = count($args);
        if ($count < 3) {
            return null;
        }

        [$dateStr, $fromFormat, $toFormat] = $args;
        $convertToIST = ($count > 3) ? $args[3] : '';
        $date = DateTime::createFromFormat($fromFormat, $dateStr);

        if (! $date) {
            return null;
        }

        // Convert to IST if flag is set
        if ($convertToIST && (strtolower($convertToIST) === 'ist')) {
            $date->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        }

        return $date->format($toFormat);
    }
}
