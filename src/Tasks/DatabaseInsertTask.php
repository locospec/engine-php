<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;

class DatabaseInsertTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.insert';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $table = $this->getTableName();
            $result = $this->operator->insert($table, $input['data']);

            return $this->formatOutput([
                'result' => $result,
                'sql' => "INSERT INTO {$table}",
            ]);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Insert operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (! isset($input['data']) || ! is_array($input['data'])) {
            throw new DatabaseOperationException('Insert task requires data array');
        }
    }
}
