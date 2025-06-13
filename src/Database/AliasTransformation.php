<?php

namespace LCSEngine\Database;

use LCSEngine\Schemas\Model\Model;

class AliasTransformation
{
    private Model $model;

    public function __construct() {}

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function transform(array $data): array
    {
        $aliases = $this->model->getAliases();
        if ($aliases->isEmpty()) {
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
        
        $aliases->each(function ($attribute, $aliasKey) use (&$processed, $record) {
            $extracted = null;
            if ($attribute->hasAliasSource()) {
                $source = $attribute->getAliasSource();

                if (strpos($source, '->') !== false) {
                    $source = preg_replace(['/^/', '/->?/'], ['', '.'], $source);
                }

                $extracted = $this->executeJMESPathExpression($record, $source);
                $processed[$aliasKey] = $extracted;
            }

            if ($attribute->hasAliasTransformation()) {
                $transormation = $attribute->getAliasTransformation();
                $valueToTransform = (! empty($extracted) && json_decode($extracted, true) !== null) ? json_decode($extracted, true) : $extracted;
                $inputData = isset($extracted) && ! empty($extracted) ? ['value' => $valueToTransform] : $record;
                $processed[$aliasKey] = $this->executeJMESPathExpression($inputData, $transormation);
            }
        });

        return $processed;
    }

    private function executeJMESPathExpression(array $data, ?string $expression): mixed
    {
        if (empty($expression)) {
            return null;
        }

        try {
            $runtime = new JMESPathCustomRuntime;
            $output = $runtime->search($expression, $data);

            return $output;
        } catch (\Exception $e) {
            return null;
            // throw $e;
        }
    }
}
