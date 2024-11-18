<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;
use Locospec\LCS\Query\FilterGroup;

class DatabaseSelectTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.select';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $table = $this->getTableName();
            $columns = $input['columns'] ?? ['*'];
            $conditions = $input['conditions'] ?? new FilterGroup();

            $result = $this->operator->select($table, $columns, $conditions);

            // $result from operator now contains ['result', 'sql', 'timing']
            return $this->formatOutput($result);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Select operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (isset($input['columns']) && !is_array($input['columns'])) {
            throw new DatabaseOperationException('Columns must be an array');
        }
    }
}
