<?php

namespace Locospec\LCS\Tasks;

class ValidateTask extends AbstractTask implements TaskInterface
{
    public function getName(): string
    {
        return 'validate';
    }

    public function execute(array $input): array
    {
        return [...$input, 'validated' => true];
    }
}
