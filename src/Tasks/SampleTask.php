<?php

namespace Locospec\Engine\Tasks;

class SampleTask extends AbstractTask
{
    public function execute(array $input): array
    {
        return $input;
    }
}
