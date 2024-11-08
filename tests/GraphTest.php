<?php

use Locospec\LCS\Edge;
use Locospec\LCS\Exceptions\DuplicateVertexException;
use Locospec\LCS\Exceptions\VertexNotFoundException;
use Locospec\LCS\Graph;
use Locospec\LCS\Vertex;

beforeEach(function () {
    $this->graph = new Graph(false); // Undirected graph

    // Create vertices for cities
    $this->vertices = [
        'Mumbai' => new Vertex('Mumbai'),
        'Dubai' => new Vertex('Dubai'),
        'London' => new Vertex('London'),
        'New York' => new Vertex('New York'),
        'Singapore' => new Vertex('Singapore'),
        'Tokyo' => new Vertex('Tokyo'),
    ];

    // Add vertices to graph
    foreach ($this->vertices as $vertex) {
        $this->graph->addVertex($vertex);
    }

    // Define connections based on adjacency list
    $this->connections = [
        'Mumbai' => ['Dubai', 'Singapore'],
        'Dubai' => ['Mumbai', 'London', 'Singapore'],
        'London' => ['Dubai', 'New York', 'Singapore'],
        'New York' => ['London'],
        'Singapore' => ['Mumbai', 'Dubai', 'London', 'Tokyo'],
        'Tokyo' => ['Singapore'],
    ];

    // Add edges to graph
    foreach ($this->connections as $source => $targets) {
        foreach ($targets as $target) {
            $edge = new Edge($this->vertices[$source], $this->vertices[$target]);
            $this->graph->addEdge($edge);
        }
    }
});

test('vertices are correctly added to graph', function () {
    expect($this->graph->hasVertex('Mumbai'))->toBeTrue()
        ->and($this->graph->hasVertex('Dubai'))->toBeTrue()
        ->and($this->graph->hasVertex('London'))->toBeTrue()
        ->and($this->graph->hasVertex('New York'))->toBeTrue()
        ->and($this->graph->hasVertex('Singapore'))->toBeTrue()
        ->and($this->graph->hasVertex('Tokyo'))->toBeTrue()
        ->and($this->graph->hasVertex('Paris'))->toBeFalse();
});

test('cannot add duplicate vertex', function () {
    $duplicateVertex = new Vertex('Mumbai');
    expect(fn() => $this->graph->addVertex($duplicateVertex))
        ->toThrow(DuplicateVertexException::class);
});

test('can retrieve vertex by id', function () {
    $vertex = $this->graph->getVertex('Mumbai');
    expect($vertex)->toBeInstanceOf(Vertex::class)
        ->and($vertex->getId())->toBe('Mumbai');
});

test('returns null for non-existent vertex', function () {
    expect($this->graph->getVertex('Paris'))->toBeNull();
});

test('all vertices are accessible', function () {
    $vertices = $this->graph->getVertices();
    expect($vertices)->toHaveCount(6)
        ->and(array_keys($vertices))->toBe([
            'Mumbai',
            'Dubai',
            'London',
            'New York',
            'Singapore',
            'Tokyo',
        ]);
});

test('neighbors are correctly added for each vertex', function () {
    // Test Mumbai's connections
    $mumbaiNeighbors = array_map(
        fn($edge) => $edge->getTarget()->getId(),
        $this->graph->getNeighbors('Mumbai')
    );
    sort($mumbaiNeighbors);
    expect($mumbaiNeighbors)->toBe(['Dubai', 'Singapore']);

    // Test Singapore's connections
    $singaporeNeighbors = array_map(
        fn($edge) => $edge->getTarget()->getId(),
        $this->graph->getNeighbors('Singapore')
    );
    sort($singaporeNeighbors); // Sort before comparing
    expect($singaporeNeighbors)->toBe(['Dubai', 'London', 'Mumbai', 'Tokyo']);

    // Test Tokyo's connections
    $tokyoNeighbors = array_map(
        fn($edge) => $edge->getTarget()->getId(),
        $this->graph->getNeighbors('Tokyo')
    );
    expect($tokyoNeighbors)->toBe(['Singapore']);
});

test('throws exception when getting neighbors of non-existent vertex', function () {
    expect(fn() => $this->graph->getNeighbors('Paris'))
        ->toThrow(VertexNotFoundException::class);
});

test('adjacency list matches expected structure', function () {
    $adjacencyList = $this->graph->getAdjacencyList();

    foreach ($this->connections as $source => $expectedTargets) {
        $actualTargets = array_map(
            fn($edge) => $edge->getTarget()->getId(),
            $adjacencyList[$source]
        );
        sort($actualTargets);
        sort($expectedTargets);
        expect($actualTargets)->toBe($expectedTargets);
    }
});

test('graph is undirected', function () {
    expect($this->graph->isDirected())->toBeFalse();

    // Verify bidirectional connections
    $mumbaiNeighbors = array_map(
        fn($edge) => $edge->getTarget()->getId(),
        $this->graph->getNeighbors('Mumbai')
    );

    // Check if Dubai has Mumbai as neighbor (bidirectional)
    $dubaiNeighbors = array_map(
        fn($edge) => $edge->getTarget()->getId(),
        $this->graph->getNeighbors('Dubai')
    );

    expect($mumbaiNeighbors)->toContain('Dubai')
        ->and($dubaiNeighbors)->toContain('Mumbai');
});

test('edge can store and retrieve data', function () {
    $source = new Vertex('A');
    $target = new Vertex('B');
    $this->graph->addVertex($source);
    $this->graph->addVertex($target);

    $data = ['distance' => 500, 'type' => 'flight'];
    $edge = new Edge($source, $target, 'flight', $data);
    $this->graph->addEdge($edge);

    $neighbors = $this->graph->getNeighbors('A');
    $edgeData = $neighbors[0]->getData();

    expect($edgeData)->toBe($data)
        ->and($neighbors[0]->getType())->toBe('flight');
});

test('directed graph maintains direction', function () {
    $directedGraph = new Graph(true);

    // Add vertices
    $a = new Vertex('A');
    $b = new Vertex('B');
    $directedGraph->addVertex($a);
    $directedGraph->addVertex($b);

    // Add one-way edge
    $edge = new Edge($a, $b);
    $directedGraph->addEdge($edge);

    expect($directedGraph->isDirected())->toBeTrue()
        ->and($directedGraph->getNeighbors('A'))->toHaveCount(1)
        ->and($directedGraph->getNeighbors('B'))->toHaveCount(0);
});
