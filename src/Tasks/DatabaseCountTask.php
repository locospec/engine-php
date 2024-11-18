<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;
use Locospec\LCS\Query\FilterGroup;

class DatabaseCountTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.count';
    }

    public function execute(array $input): array
    {
        try {
            $table = $this->getTableName();
            $conditions = $input['conditions'] ?? new FilterGroup;

            $result = $this->operator->count($table, $conditions);

            return $this->formatOutput([
                'result' => $result,
                'sql' => "SELECT COUNT(*) FROM {$table}",
            ]);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Count operation failed: {$e->getMessage()}");
        }
    }
}
