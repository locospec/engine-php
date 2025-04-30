<?php

namespace Locospec\Engine\Tasks;

class AuthorizeTask extends AbstractTask
{
    public function getName(): string
    {
        return 'authorize';
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        return ['authorized' => true];
    }
}
