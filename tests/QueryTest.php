<?php

use Locospec\LCS\Query\Query;
use Locospec\LCS\Query\FilterGroup;
use Locospec\LCS\Query\FilterCondition;
use Locospec\LCS\Query\Sort;
use Locospec\LCS\Query\SortCollection;
use Locospec\LCS\Query\OffsetPagination;
use Locospec\LCS\Query\CursorPagination;

beforeEach(function () {
    $this->sampleQueryData = [
        'filters' => [
            'operator' => 'and',
            'conditions' => [
                [
                    'attribute' => 'name',
                    'operator' => '=',
                    'value' => 'Test'
                ],
                [
                    'operator' => 'or',
                    'conditions' => [
                        [
                            'attribute' => 'age',
                            'operator' => '>',
                            'value' => 18
                        ],
                        [
                            'attribute' => 'age',
                            'operator' => '<',
                            'value' => 65
                        ]
                    ]
                ],
                [
                    'attribute' => 'city.name',
                    'operator' => 'LIKE',
                    'value' => '%London%'
                ]
            ]
        ],
        'sorts' => [
            [
                'attribute' => 'created_at',
                'direction' => 'desc'
            ],
            [
                'attribute' => 'city.name',
                'direction' => 'asc'
            ]
        ],
        'pagination' => [
            'page' => 2,
            'per_page' => 15
        ]
    ];
});

test('can create query from array with all components', function () {
    $query = Query::fromArray($this->sampleQueryData, 'users');

    // Test basic query properties
    expect($query->getModelName())->toBe('users');
    expect($query->getFilters())->toBeInstanceOf(FilterGroup::class);
    expect($query->getSorts())->toBeInstanceOf(SortCollection::class);
    expect($query->getPagination())->toBeInstanceOf(OffsetPagination::class);
});

test('filters are properly constructed', function () {
    $query = Query::fromArray($this->sampleQueryData, 'users');
    $filters = $query->getFilters();

    expect($filters->getOperator())->toBe('and');
    expect($filters->getConditions())->toHaveCount(3);

    // Test first simple condition
    $firstCondition = $filters->getConditions()[0];
    expect($firstCondition->getAttribute())->toBe('name');
    expect($firstCondition->getOperator())->toBe('=');
    expect($firstCondition->getValue())->toBe('Test');

    // Test nested OR condition
    $secondCondition = $filters->getConditions()[1];
    expect($secondCondition->isCompound())->toBeTrue();
    expect($secondCondition->getNestedConditions()->getOperator())->toBe('or');

    // Test relationship condition
    $thirdCondition = $filters->getConditions()[2];
    expect($thirdCondition->getAttributePath()->isRelationshipPath())->toBeTrue();
    expect($thirdCondition->getAttributePath()->getRelationshipPath())->toBe('city');
});

test('sorts are properly constructed', function () {
    $query = Query::fromArray($this->sampleQueryData, 'users');
    $sorts = $query->getSorts()->getSorts();

    expect($sorts)->toHaveCount(2);

    // Test first sort
    expect($sorts[0]->getAttribute())->toBe('created_at');
    expect($sorts[0]->getDirection())->toBe('desc');

    // Test relationship sort
    expect($sorts[1]->getAttributePath()->isRelationshipPath())->toBeTrue();
    expect($sorts[1]->getAttributePath()->getRelationshipPath())->toBe('city');
});

test('offset pagination is properly constructed', function () {
    $query = Query::fromArray($this->sampleQueryData, 'users');
    $pagination = $query->getPagination();

    expect($pagination)->toBeInstanceOf(OffsetPagination::class);
    expect($pagination->getPage())->toBe(2);
    expect($pagination->getPerPage())->toBe(15);
    expect($pagination->getOffset())->toBe(15);
});

test('cursor pagination is properly constructed', function () {
    $cursorData = array_merge($this->sampleQueryData, [
        'pagination' => [
            'cursor' => base64_encode('last_id_123'),
            'limit' => 20,
            'cursor_column' => 'id'
        ]
    ]);

    $query = Query::fromArray($cursorData, 'users');
    $pagination = $query->getPagination();

    expect($pagination)->toBeInstanceOf(CursorPagination::class);
    expect($pagination->getCursor())->toBe(base64_encode('last_id_123'));
    expect($pagination->getLimit())->toBe(20);
    expect($pagination->getCursorColumn())->toBe('id');
});

test('query can be converted back to array', function () {
    $query = Query::fromArray($this->sampleQueryData, 'users');
    $array = $query->toArray();

    // Test structure
    expect($array)->toHaveKey('model');
    expect($array['model'])->toBe('users');

    // Test filters
    expect($array['filters'])->toMatchArray($this->sampleQueryData['filters']);

    // Test sorts
    expect($array['sorts'])->toMatchArray($this->sampleQueryData['sorts']);

    // Test pagination - compare specific keys
    expect($array['pagination'])->toMatchArray([
        'page' => $this->sampleQueryData['pagination']['page'],
        'per_page' => $this->sampleQueryData['pagination']['per_page']
    ]);
});

test('empty query components are handled properly', function () {
    $emptyData = [
        'sorts' => [],
        'filters' => [],
        'pagination' => []
    ];

    $query = Query::fromArray($emptyData, 'users');
    $array = $query->toArray();

    // Check that empty components are not included in the array output
    expect($query->getFilters())->toBeNull();
    expect($query->getSorts())->toBeNull();
    expect($query->getPagination())->toBeNull();
    expect($array)->toHaveKey('model');
    expect($array)->not->toHaveKey('filters');
    expect($array)->not->toHaveKey('sorts');
    expect($array)->not->toHaveKey('pagination');
});

test('relationship paths are properly parsed', function () {
    $data = [
        'filters' => [
            'operator' => 'and',
            'conditions' => [
                [
                    'attribute' => 'department.company.name',
                    'operator' => '=',
                    'value' => 'Acme Corp'
                ]
            ]
        ]
    ];

    $query = Query::fromArray($data, 'users');
    $condition = $query->getFilters()->getConditions()[0];
    $path = $condition->getAttributePath();

    expect($path->isRelationshipPath())->toBeTrue();
    expect($path->getSegments())->toHaveCount(3);
    expect($path->getRelationshipPath())->toBe('department.company');
    expect($path->getAttributeName())->toBe('name');
});
