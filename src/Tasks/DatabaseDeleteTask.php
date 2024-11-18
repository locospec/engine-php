<?php

namespace Locospec\LCS\Tasks;

use Locospec\LCS\Exceptions\DatabaseOperationException;
use Locospec\LCS\Query\FilterGroup;

class DatabaseDeleteTask extends AbstractDatabaseTask
{
    public function getName(): string
    {
        return 'database.delete';
    }

    public function execute(array $input): array
    {
        $this->validateInput($input);

        try {
            $table = $this->getTableName();
            $conditions = $input['conditions'] ?? new FilterGroup();
            $softDelete = $input['soft_delete'] ?? false;

            if ($softDelete) {
                $result = $this->operator->softDelete($table, $conditions);
            } else {
                $result = $this->operator->delete($table, $conditions);
            }

            return $this->formatOutput([
                'result' => $result,
                'sql' => ($softDelete ? "UPDATE {$table} SET deleted_at" : "DELETE FROM {$table}")
            ]);
        } catch (\Exception $e) {
            throw new DatabaseOperationException("Delete operation failed: {$e->getMessage()}");
        }
    }

    private function validateInput(array $input): void
    {
        if (!isset($input['conditions'])) {
            throw new DatabaseOperationException('Delete task requires conditions');
        }
    }
}
