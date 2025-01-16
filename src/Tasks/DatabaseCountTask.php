<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Exceptions\DatabaseOperationException;
use Locospec\Engine\Query\FilterGroup;

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

            // $result from operator now contains ['result', 'sql', 'timing']
            return $this->formatOutput($result);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Count operation failed: {$e->getMessage()}");
        }
    }
}
