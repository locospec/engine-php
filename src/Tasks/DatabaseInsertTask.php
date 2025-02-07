<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Exceptions\DatabaseOperationException;

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

            // $result from operator now contains ['result', 'sql', 'timing']
            return $this->formatOutput($result);
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
