<?php

use Locospec\EnginePhp\Edge;
use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Graph;
use Locospec\EnginePhp\BFTGraph;
use Locospec\EnginePhp\TreeNode;
use Locospec\EnginePhp\Vertex;

beforeEach(function () {
    // Create a sample graph for testing
    $this->graph = new Graph(false); // Undirected graph

    // Create vertices
    $this->vertices = [
        'A' => new Vertex('A'),
        'B' => new Vertex('B'),
        'C' => new Vertex('C'),
        'D' => new Vertex('D'),
        'E' => new Vertex('E'),
        'F' => new Vertex('F'),
    ];

    // Add vertices to graph
    foreach ($this->vertices as $vertex) {
        $this->graph->addVertex($vertex);
    }

    // Define edges for a more complex graph
    $edges = [
        ['A', 'B'],
        ['A', 'C'],
        ['B', 'D'],
        ['C', 'D'],
        ['D', 'E'],
        ['C', 'F'],
    ];

    // Add edges to graph
    foreach ($edges as [$source, $target]) {
        $edge = new Edge($this->vertices[$source], $this->vertices[$target]);
        $this->graph->addEdge($edge);
    }

    $this->bftGraph = new BFTGraph($this->graph);
});

test('throws exception for vertex not in graph', function () {
    $invalidVertex = new Vertex('X');
    expect(fn() => $this->bftGraph->generateTree($invalidVertex))
        ->toThrow(InvalidArgumentException::class, 'Vertex is not present in the graph');
});

test('generates tree in correct BFT order', function () {
    $tree = $this->bftGraph->generateTree($this->vertices['A']);

    // Collect vertices by level while tracking visited vertices
    $levels = [];
    $visited = [];
    $queue = new SplQueue();
    $queue->enqueue([$tree, 0]); // [node, level]
    $visited[$tree->vertex->getId()] = true;

    while (!$queue->isEmpty()) {
        [$node, $level] = $queue->dequeue();
        $levels[$level][] = $node->vertex->getId();

        foreach ($node->children as $child) {
            $childId = $child->vertex->getId();
            if (!isset($visited[$childId])) {
                $visited[$childId] = true;
                $queue->enqueue([$child, $level + 1]);
            }
        }
    }

    // Level 0 should be A
    expect($levels[0])->toBe(['A']);

    // Level 1 should contain B and C (immediate neighbors of A)
    sort($levels[1]);
    expect($levels[1])->toBe(['B', 'C']);

    // Level 2 should contain D and F (D is only added once even though reachable from both B and C)
    sort($levels[2]);
    expect($levels[2])->toBe(['D', 'F']);

    // Level 3 should contain E
    expect($levels[3])->toBe(['E']);
});

test('handles cycles correctly', function () {
    // Add an edge to create a cycle
    $cycleEdge = new Edge($this->vertices['E'], $this->vertices['B']);
    $this->graph->addEdge($cycleEdge);

    $tree = $this->bftGraph->generateTree($this->vertices['A']);

    // Track visited vertex IDs instead of vertex objects
    $visitedIds = [];
    $queue = new SplQueue();
    $queue->enqueue($tree);

    while (!$queue->isEmpty()) {
        $node = $queue->dequeue();
        $vertexId = $node->vertex->getId();

        // Ensure we haven't seen this vertex ID before
        expect(isset($visitedIds[$vertexId]))->toBeFalse();
        $visitedIds[$vertexId] = true;

        foreach ($node->children as $child) {
            $queue->enqueue($child);
        }
    }

    // Should have visited each vertex exactly once
    expect(count($visitedIds))->toBe(count($this->vertices));
});

test('generates valid mermaid syntax', function () {
    $tree = $this->bftGraph->generateTree($this->vertices['A']);
    $mermaidSyntax = $this->bftGraph->treeToMermaidSyntax($tree);

    expect($mermaidSyntax)->toContain('graph TD')
        ->and($mermaidSyntax)->toContain('A --> B')
        ->and($mermaidSyntax)->toContain('A --> C')
        ->and($mermaidSyntax)->toContain('B --> D')
        ->and($mermaidSyntax)->toContain('D --> E')
        ->and($mermaidSyntax)->toContain('C --> F');
});

test('tree node can find path to target', function () {
    $tree = $this->bftGraph->generateTree($this->vertices['A']);

    // Test path from A to E
    $pathToE = $tree->findPath('E');
    expect($pathToE)->toBe(['A', 'B', 'D', 'E']);

    // Test path from A to F
    $pathToF = $tree->findPath('F');
    expect($pathToF)->toBe(['A', 'C', 'F']);

    // Test non-existent path
    $nonExistentPath = $tree->findPath('X');
    expect($nonExistentPath)->toBeNull();
});

test('tree node can detect if path exists', function () {
    $tree = $this->bftGraph->generateTree($this->vertices['A']);

    expect($tree->hasPath('E'))->toBeTrue()
        ->and($tree->hasPath('F'))->toBeTrue()
        ->and($tree->hasPath('X'))->toBeFalse();
});

test('tree node converts correctly to array', function () {
    $tree = $this->bftGraph->generateTree($this->vertices['A']);
    $array = $tree->toArray();

    expect($array)->toHaveKey('id')
        ->and($array)->toHaveKey('children')
        ->and($array['id'])->toBe('A')
        ->and($array['children'])->toBeArray();

    // Verify first level children
    $childrenIds = array_map(fn($child) => $child['id'], $array['children']);
    sort($childrenIds);
    expect($childrenIds)->toBe(['B', 'C']);
});
