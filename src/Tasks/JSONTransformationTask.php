<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\Exceptions\InvalidArgumentException;
use Symfony\Component\Process\Process;

class JSONTransformationTask extends AbstractTask
{
    public function getName(): string
    {
        return 'json_transform';
    }

    public function execute(array $input): array
    {
        try {
            // Validate input has required fields
            if (! isset($input['json_data'])) {
                throw new InvalidArgumentException('Input JSON data is required');
            }

            if (! isset($input['jq_filter'])) {
                throw new InvalidArgumentException('JQ filter is required');
            }

            // Convert input json_data to string if it's an array
            $inputJson = is_array($input['json_data']) ?
                json_encode($input['json_data']) :
                $input['json_data'];

            // Create and configure process
            $process = new Process(['jq', '-M', $input['jq_filter']]);
            $process->setInput($inputJson);

            // Run the transformation
            $process->run();

            // Check for errors
            if (! $process->isSuccessful()) {
                return [
                    'success' => false,
                    'error' => $process->getErrorOutput(),
                    'input' => $input['json_data'],
                    'filter' => $input['jq_filter'],
                ];
            }

            // Get and decode the result
            $result = trim($process->getOutput());
            $transformedData = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'JSON decode error: '.json_last_error_msg(),
                    'raw_output' => $result,
                ];
            }

            return [
                'success' => true,
                'data' => $transformedData,
                'metadata' => [
                    'timestamp' => microtime(true),
                    'filter' => $input['jq_filter'],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }
}
