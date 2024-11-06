<?php

namespace Locospec\EnginePhp\Tasks;

class AuthorizeTask extends AbstractTask
{
    public function getName(): string
    {
        return 'authorize';
    }

    public function execute(array $input): array
    {
        return ["authorized" => true];
    }
}
