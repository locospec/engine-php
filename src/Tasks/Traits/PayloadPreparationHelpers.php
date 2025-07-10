<?php

namespace LCSEngine\Tasks\Traits;

use LCSEngine\Tasks\DTOs\ReadPayload;

trait PayloadPreparationHelpers
{
    /**
     * Handles pagination logic for read operations.
     *
     * @param array $payload The incoming payload.
     * @param object|array $preparedPayload The payload being prepared (DTO or array).
     */
    private function preparePagination(array $payload, array &$preparedPayload): void
    {
        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload['pagination'] = $payload['pagination'];
        }
    }

    /**
     * Handles sorting logic, ensuring the primary key is always included as a final sort criterion for stable ordering.
     *
     * @param array $payload The incoming payload.
     * @param array $preparedPayload The payload being prepared (passed by reference).
     * @param string $primaryKeyAttributeKey The name of the primary key attribute.
     */
    private function prepareSorts(array $payload, array &$preparedPayload, string $primaryKeyAttributeKey): void
    {
        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload['sorts'] = $payload['sorts'];

            // Check if primary key exists in sorts
            $primaryKeyExists = false;
            foreach ($payload['sorts'] as $sort) {
                if (isset($sort['attribute']) && $sort['attribute'] === $primaryKeyAttributeKey) {
                    $primaryKeyExists = true;
                    break;
                }
            }

            // Add primary key to end of sorts if it doesn't exist
            if (! $primaryKeyExists) {
                $preparedPayload['sorts'][] = [
                    'attribute' => $primaryKeyAttributeKey,
                    'direction' => 'ASC',
                ];
            }
        } else {
            $preparedPayload['sorts'] = [[
                'attribute' => $primaryKeyAttributeKey,
                'direction' => 'ASC',
            ]];
        }
    }

    /**
     * Handles pagination logic for read operations using a DTO.
     *
     * @param array $payload The incoming payload.
     * @param ReadPayload $preparedPayload The payload being prepared.
     */
    private function preparePaginationForDto(array $payload, ReadPayload $preparedPayload): void
    {
        if (isset($payload['pagination']) && ! empty($payload['pagination'])) {
            if (! isset($payload['pagination']['cursor'])) {
                unset($payload['pagination']['cursor']);
            }

            $preparedPayload->pagination = $payload['pagination'];
        }
    }

    /**
     * Handles sorting logic for a DTO, ensuring the primary key is always included as a final sort criterion for stable ordering.
     *
     * @param array $payload The incoming payload.
     * @param ReadPayload $preparedPayload The payload being prepared.
     * @param string $primaryKeyAttributeKey The name of the primary key attribute.
     */
    private function prepareSortsForDto(array $payload, ReadPayload $preparedPayload, string $primaryKeyAttributeKey): void
    {
        if (isset($payload['sorts']) && ! empty($payload['sorts'])) {
            $preparedPayload->sorts = $payload['sorts'];

            // Check if primary key exists in sorts
            $primaryKeyExists = false;
            foreach ($payload['sorts'] as $sort) {
                if (isset($sort['attribute']) && $sort['attribute'] === $primaryKeyAttributeKey) {
                    $primaryKeyExists = true;
                    break;
                }
            }

            // Add primary key to end of sorts if it doesn't exist
            if (! $primaryKeyExists) {
                $preparedPayload->sorts[] = [
                    'attribute' => $primaryKeyAttributeKey,
                    'direction' => 'ASC',
                ];
            }
        } else {
            $preparedPayload->sorts = [[ 
                'attribute' => $primaryKeyAttributeKey,
                'direction' => 'ASC',
            ]];
        }
    }


}
