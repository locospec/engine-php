<?php

namespace Locospec\Engine\Database;

use Locospec\Engine\Models\ModelDefinition;

require base_Path('/vendor/autoload.php');
use JmesPath\Env as JmesPath;

class AliasTransformation
{
    private ModelDefinition $model;

    public function __construct() {}

    public function setModel(ModelDefinition $model)
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
            $extracted = null;
            if (isset($expression->source)) {
                $source = $expression->source;

                if (strpos($expression->source, '->') !== false) {
                    $source = preg_replace(['/^/', '/->?/'], ['', '.'], $expression->source);
                }

                $extracted = $this->executeJMESPathExpression($record, $source);
                $processed[$aliasKey] = $extracted;
            }

            if (isset($expression->transform)) {
                $valueToTransform = (! empty($extracted) && json_decode($extracted, true) !== null) ? json_decode($extracted, true) : $extracted;
                $inputData = isset($extracted) && ! empty($extracted) ? ['value' => $valueToTransform] : $record;
                $processed[$aliasKey] = $this->executeJMESPathExpression($inputData, $expression->transform);
            }
        }

        return $processed;
    }

    private function executeJMESPathExpression(array $data, ?string $expression): mixed
    {
        if (empty($expression)) {
            return null;
        }

        try {
            $output = JmesPath::search($expression, $data);

            return $output;
        } catch (\Exception $e) {
            dd($e);
            throw $e;
        }

    }
}
