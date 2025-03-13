<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\SpecValidator;
use RuntimeException;

class ValidateTask extends AbstractTask implements TaskInterface
{
    public function getName(): string
    {
        return 'validate';
    }

    public function execute(array $input): array
    {
        $currentOperation =  $this->context->get('action');
        $validator = new SpecValidator;

        switch ($this->context->get('action')) {
            case '_create':
                $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload']);
                break;

            case '_update':
                $validation = $this->validatePayloadForCreateAndUpdate($input['preparedPayload']);
                break;

            default:
                break;
        }

        if($currentOperation=== "_update"){
            $data = $input['preparedPayload']['data'][0];
            unset($input['preparedPayload']['data']);
            $input['preparedPayload']['filters'] = [
                'uuid' => $data['uuid']
            ];
            unset($data['uuid']);
            $input['preparedPayload']['data'] = $data;
        }
        
        $validation = $validator->validateOperation($input['preparedPayload']);

        if (! $validation['isValid']) {
            throw new RuntimeException(
                'Invalid operation: '.json_encode($validation['errors'])
            );
        }

        return [...$input, 'validated' => $validation['isValid']];
    }

    public function validatePayloadForCreateAndUpdate(array $payload): array
    {
        $currentOperation =  $this->context->get('action');
        $validator = $this->context->get('crudValidator');
        $model = $this->context->get('model');
        $attributes = $this->context->get('model')->getAttributes()->getAttributes();
        $errors = [];
        
        // Ensure "data" is an array of records
        $records = $payload['data'] ?? [];

        // Validate each record individually using the attributes.
        foreach ($records as $index => $record) {
            $result = $validator->validate($record, $attributes, $currentOperation);
            // If the validator returns errors (not true), capture them.
            if ($result !== true) {
                $errors[$index] = $result;
            }
        }

        // Return validation errors if any
        if (!empty($errors)) {
            throw new RuntimeException($errors[0]);
        }

        return [
            'isValid' => true,
        ];
    }
}
