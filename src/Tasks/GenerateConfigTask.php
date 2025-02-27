<?php

namespace Locospec\Engine\Tasks;

use Locospec\Engine\StateMachine\ContextInterface;

class GenerateConfigTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'generate_config';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input): array
    {

        $attributes = $this->context->get('model')->getAttributes()->toObject();
        $filterable = $this->context->get('model')->getFilterable();
        // Todo: Get default view
        // - For now default view is hardcoded, for the asset type table

        $result = [
            'selectionType' => 'none',
            'attributes' => $attributes,
            'filterable' => $filterable,
            'defaultView' => [
                'selectionType' => 'none',
                'attributes' => [
                    'uuid' => ['type' => 'uuid', 'label' => 'ID'],
                    'name' => ['type' => 'string', 'label' => 'Name'],
                    'asset_type_name' => ['type' => 'string', 'label' => 'Asset Type'],
                ],
                'filters' => [
                    'asset_type_name' => [
                        'type' => 'enum',
                        'label' => 'Asset Type',
                        'modelName' => 'asset_type',
                        'isNullable' => false,
                    ],
                    'name' => [
                        'type' => 'enum',
                        'label' => 'Sub Asset Type',
                        'modelName' => 'sub_asset_type',
                        'dependsOn' => ['asset_type_name'],
                        'isNullable' => false,
                    ],
                ],
            ],
        ];

        return $result;
    }
}
