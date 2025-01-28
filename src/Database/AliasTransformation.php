<?php

namespace Locospec\Engine\Database;

use Locospec\Engine\Models\ModelDefinition;
use Symfony\Component\Process\Process;

class AliasTransformation
{
    private ModelDefinition $model;

    public function __construct(ModelDefinition $model)
    {
        $this->model = $model;
    }

    public function transform(array $data): array
    {
        $aliases = $this->model->getAliases();
        if (empty($aliases)) {
            return $data;
        }

        $isCollection = is_array($data) && ! empty($data) && ! isset($data['id']);
        $records = $isCollection ? $data : [$data];

        $transformedRecords = [];

        foreach ($records as $record) {
            $transformedRecords[] = $this->processRecord($record, $aliases);
        }

        return $isCollection ? $transformedRecords : $transformedRecords[0];
    }

    private function processRecord(array $record, object $aliases): array
    {
        $processed = $record;

        foreach ($aliases as $aliasKey => $expression) {
            $extracted = $this->executeJQExpression($record, $expression);
            $processed[$aliasKey] = $extracted;
        }

        return $processed;
    }

    private function executeJQExpression(array $data, ?string $expression): mixed
    {
        if (empty($expression)) {
            return null;
        }
        $process = new Process(['jq', '-r', $expression]);
        $process->setInput(json_encode($data));

        try {
            $process->mustRun();

            if (! $process->isSuccessful()) {
                return null;
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return trim($process->getOutput()) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
