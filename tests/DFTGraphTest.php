<?php

use Locospec\EnginePhp\DFTGraph;
use Locospec\EnginePhp\Edge;
use Locospec\EnginePhp\Exceptions\InvalidArgumentException;
use Locospec\EnginePhp\Graph;
use Locospec\EnginePhp\Vertex;

beforeEach(function () {
    $this->graph = new Graph(false); // Undirected graph

    // Create vertices
    $this->vertices = [
        'A' => new Vertex('A'),
        'B' => new Vertex('B'),
        'C' => new Vertex('C'),
        'D' => new Vertex('D'),
        'E' => new Vertex('E'),
    ];

    // Add vertices to graph
    foreach ($this->vertices as $vertex) {
        $this->graph->addVertex($vertex);
    }

    // Define edges
    $edges = [
        ['A', 'B'],
        ['A', 'C'],
        ['B', 'D'],
        ['C', 'D'],
        ['D', 'E'],
    ];

    // Add edges to graph
    foreach ($edges as [$source, $target]) {
        $edge = new Edge($this->vertices[$source], $this->vertices[$target]);
        $this->graph->addEdge($edge);
    }

    $this->dftGraph = new DFTGraph($this->graph);
});

test('throws exception for vertex not in graph', function () {
    $invalidVertex = new Vertex('X');
    expect(fn () => $this->dftGraph->generateTree($invalidVertex))
        ->toThrow(InvalidArgumentException::class);
});

test('generates tree containing all possible paths', function () {
    $tree = $this->dftGraph->generateTree($this->vertices['A']);

    // Helper to collect all paths to E
    $pathsToE = [];
    $collectPaths = function ($node, $currentPath) use (&$collectPaths, &$pathsToE) {
        $currentPath[] = $node->vertex->getId();

        if ($node->vertex->getId() === 'E') {
            $pathsToE[] = $currentPath;

            return;
        }

        foreach ($node->children as $child) {
            $collectPaths($child, $currentPath);
        }
    };

    $collectPaths($tree, []);

    // Sort paths for consistent comparison
    foreach ($pathsToE as &$path) {
        sort($path);
    }

    // Should find all possible paths from A to E
    expect(count($pathsToE))->toBe(2)
        ->and($pathsToE)->toContain(['A', 'B', 'D', 'E'])
        ->and($pathsToE)->toContain(['A', 'C', 'D', 'E']);
});

test('generates valid mermaid syntax showing all paths', function () {
    $tree = $this->dftGraph->generateTree($this->vertices['A']);
    $mermaidSyntax = $this->dftGraph->treeToMermaidSyntax($tree);

    // Should contain all possible edges in the paths
    expect($mermaidSyntax)->toContain('graph TD')
        ->and($mermaidSyntax)->toContain('A --> B')
        ->and($mermaidSyntax)->toContain('A --> C')
        ->and($mermaidSyntax)->toContain('B --> D')
        ->and($mermaidSyntax)->toContain('C --> D')
        ->and($mermaidSyntax)->toContain('D --> E');
});
