<?php

namespace Locospec\LCS\Tasks;

class InsertDBTask extends AbstractTask
{
    public function getName(): string
    {
        return 'insert_db';
    }

    public function execute(array $input): array
    {
        return $input;
    }
}
