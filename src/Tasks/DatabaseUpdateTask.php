<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;
use Locospec\LCS\Query\FilterGroup;

class DatabaseUpdateTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.update';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $table = $this->getTableName();
            $conditions = $input['conditions'] ?? new FilterGroup;

            $result = $this->operator->update(
                $table,
                $input['data'],
                $conditions
            );

            return $this->formatOutput([
                'result' => $result,
                'sql' => "UPDATE {$table}",
            ]);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Update operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (! isset($input['data']) || ! is_array($input['data'])) {
            throw new DatabaseOperationException('Update task requires data array');
        }
    }
}
