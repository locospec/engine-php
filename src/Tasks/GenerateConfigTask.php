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

        // $attributes = $this->context->get('model')->getAttributes()->toObject();
        // $filterable = $this->context->get('model')->getFilterable();
        
        // Get view
        $view =  $this->context->get('view');

        // $result = [
        //     'attributes' => $attributes,
        //     'filterable' => $filterable,
        //     'defaultView' => $view->toObject(),
            // [
            //     'selectionType' => 'none',
            //     'attributes' => [
            //         'uuid' => ['type' => 'uuid', 'label' => 'ID'],
            //         'name' => ['type' => 'string', 'label' => 'Name'],
            //         'asset_type_name' => ['type' => 'string', 'label' => 'Asset Type'],
            //     ],
            //     'filters' => [
            //         'asset_type_name' => [
            //             'type' => 'enum',
            //             'label' => 'Asset Type',
            //             'modelName' => 'asset_type',
            //             'isNullable' => false, // remove this
            //         ],
            //         'name' => [
            //             'type' => 'enum',
            //             'label' => 'Sub Asset Type',
            //             'modelName' => 'sub_asset_type',
            //             'dependsOn' => ['asset_type_name'],
            //             'isNullable' => false, // remove this
            //         ],
            //     ],
            // ],
        // ];
        $result = [
            'view' => $view->toObject()
        ];

        return $result;
    }
}
