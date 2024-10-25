<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Exceptions\DuplicateVertexException;
use Locospec\EnginePhp\Exceptions\VertexNotFoundException;

class Graph
{
    /**
     * @var array<mixed, Vertex> Map of vertex IDs to Vertex instances
     */
    private array $vertices = [];

    /**
     * @var array<mixed, Edge[]> Map of vertex IDs to arrays of outgoing edges
     */
    private array $adjacencyList = [];

    /**
     * @var bool Whether the graph is directed
     */
    private readonly bool $directed;

    /**
     * Creates a new Graph instance
     *
     * @param  bool  $directed  Whether the graph is directed (default: false)
     */
    public function __construct(bool $directed = false)
    {
        $this->directed = $directed;
    }

    /**
     * Adds a vertex to the graph
     *
     * @param  Vertex  $vertex  The vertex to add
     *
     * @throws DuplicateVertexException If vertex with same ID already exists
     */
    public function addVertex(Vertex $vertex): void
    {
        $vertexId = $vertex->getId();

        if ($this->hasVertex($vertexId)) {
            throw new DuplicateVertexException("Vertex with ID {$vertexId} already exists");
        }

        $this->vertices[$vertexId] = $vertex;
        $this->adjacencyList[$vertexId] = [];
    }

    /**
     * Checks if an edge already exists between source and target vertices
     *
     * @param  mixed  $sourceId  Source vertex ID
     * @param  mixed  $targetId  Target vertex ID
     */
    private function hasEdge(mixed $sourceId, mixed $targetId): bool
    {
        if (! isset($this->adjacencyList[$sourceId])) {
            return false;
        }

        foreach ($this->adjacencyList[$sourceId] as $edge) {
            if ($edge->getTarget()->getId() === $targetId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds an edge to the graph
     *
     * @param  Edge  $edge  The edge to add
     *
     * @throws VertexNotFoundException If either source or target vertex doesn't exist
     */
    public function addEdge(Edge $edge): void
    {
        $sourceId = $edge->getSource()->getId();
        $targetId = $edge->getTarget()->getId();

        if (! $this->hasVertex($sourceId)) {
            throw new VertexNotFoundException("Source vertex with ID {$sourceId} not found");
        }
        if (! $this->hasVertex($targetId)) {
            throw new VertexNotFoundException("Target vertex with ID {$targetId} not found");
        }

        // Only add edge if it doesn't already exist
        if (! $this->hasEdge($sourceId, $targetId)) {
            $this->adjacencyList[$sourceId][] = $edge;
        }

        if (! $this->directed && ! $this->hasEdge($targetId, $sourceId)) {
            // For undirected graphs, create reverse edge if it doesn't exist
            $reverseEdge = new Edge(
                $edge->getTarget(),
                $edge->getSource(),
                $edge->getType(),
                $edge->getData()
            );
            $this->adjacencyList[$targetId][] = $reverseEdge;
        }
    }

    /**
     * Returns all edges connected to the given vertex
     *
     * @param  mixed  $vertexId  The ID of the vertex
     * @return Edge[] Array of connected edges
     *
     * @throws VertexNotFoundException If vertex doesn't exist
     */
    public function getNeighbors(mixed $vertexId): array
    {
        if (! $this->hasVertex($vertexId)) {
            throw new VertexNotFoundException("Vertex with ID {$vertexId} not found");
        }

        return $this->adjacencyList[$vertexId];
    }

    /**
     * Checks if a vertex exists in the graph
     *
     * @param  mixed  $vertexId  The ID to check
     * @return bool True if vertex exists, false otherwise
     */
    public function hasVertex(mixed $vertexId): bool
    {
        return isset($this->vertices[$vertexId]);
    }

    /**
     * Returns a vertex by its ID
     *
     * @param  mixed  $vertexId  The ID of the vertex to get
     * @return Vertex|null The vertex if found, null otherwise
     */
    public function getVertex(mixed $vertexId): ?Vertex
    {
        return $this->vertices[$vertexId] ?? null;
    }

    /**
     * Returns all vertices in the graph
     *
     * @return array<mixed, Vertex>
     */
    public function getVertices(): array
    {
        return $this->vertices;
    }

    /**
     * Returns the complete adjacency list
     *
     * @return array<mixed, Edge[]>
     */
    public function getAdjacencyList(): array
    {
        return $this->adjacencyList;
    }

    /**
     * Returns whether the graph is directed
     */
    public function isDirected(): bool
    {
        return $this->directed;
    }

    /**
     * Converts the graph to Mermaid diagram syntax
     *
     * @return string Mermaid graph syntax
     */
    public function toMermaidSyntax(): string
    {
        $lines = ['graph TD'];

        foreach ($this->getAdjacencyList() as $sourceId => $edges) {
            foreach ($edges as $edge) {
                // dd($edge);
                // Replace spaces with underscores and escape special characters
                $safeSource = preg_replace(
                    '/[^a-zA-Z0-9_]/',
                    '',
                    str_replace(' ', '_', (string)$sourceId)
                );

                $safeTarget = preg_replace(
                    '/[^a-zA-Z0-9_]/',
                    '',
                    str_replace(' ', '_', (string)$edge->getTarget()->getId())
                );

                $lines[] = sprintf(
                    '    %s%s%s%s',
                    $safeSource,
                    $this->isDirected() ? ' -->' : ' ---',
                    $edge->getType() !== null ? sprintf('|%s|', preg_replace('/[^\w\s-]/', '', $edge->getType())) : '',
                    $safeTarget
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Find shortest path between two vertices using BFS
     *
     * @param mixed $sourceId Starting vertex ID
     * @param mixed $targetId Target vertex ID
     * @return Edge[]|null Array of edges representing shortest path, or null if no path exists
     * @throws VertexNotFoundException If either vertex doesn't exist
     */
    public function findShortestPath(mixed $sourceId, mixed $targetId): ?array
    {
        if (!$this->hasVertex($sourceId)) {
            throw new VertexNotFoundException("Source vertex with ID {$sourceId} not found");
        }
        if (!$this->hasVertex($targetId)) {
            throw new VertexNotFoundException("Target vertex with ID {$targetId} not found");
        }

        // If source and target are same, return empty path
        if ($sourceId === $targetId) {
            return [];
        }

        // Track visited vertices and their parents with the connecting edge
        $visited = [$sourceId => true];
        $parentEdges = [];

        // Use queue for BFS
        $queue = new \SplQueue();
        $queue->enqueue($sourceId);

        while (!$queue->isEmpty()) {
            $currentId = $queue->dequeue();
            $neighbors = $this->getNeighbors($currentId);

            foreach ($neighbors as $edge) {
                $neighborId = $edge->getTarget()->getId();

                if (!isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $parentEdges[$neighborId] = $edge;
                    $queue->enqueue($neighborId);

                    if ($neighborId === $targetId) {
                        return $this->reconstructEdgePath($parentEdges, $sourceId, $targetId);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Reconstruct path from parent edges map
     *
     * @param array<mixed, Edge> $parentEdges Map of vertex IDs to their parent edges
     * @param mixed $sourceId Starting vertex ID
     * @param mixed $targetId Target vertex ID
     * @return Edge[] Array of edges representing the path
     */
    private function reconstructEdgePath(array $parentEdges, mixed $sourceId, mixed $targetId): array
    {
        $path = [];
        $current = $targetId;

        while ($current !== $sourceId) {
            $edge = $parentEdges[$current];
            array_unshift($path, $edge);
            $current = $edge->getSource()->getId();
        }

        return $path;
    }


    /**
     * Find all reachable entities and their relationship paths
     *
     * @param mixed $sourceId Starting entity
     * @return array<string, array{path: Edge[], type: string}> Map of target entities to their paths
     */
    public function findAllRelationships(mixed $sourceId): array
    {
        $relationships = [];
        $visited = [];

        foreach ($this->vertices as $targetId => $vertex) {
            if ($targetId !== $sourceId) {
                $path = $this->findShortestPath($sourceId, $targetId);
                if ($path !== null) {
                    // Determine overall relationship type based on path
                    $relationType = $this->determineRelationType($path);
                    $relationships[$targetId] = [
                        'path' => $path,
                        'type' => $relationType
                    ];
                }
            }
        }

        return $relationships;
    }

    private function determineRelationType(array $edges): string
    {
        // Composite relationship based on path
        $hasMany = false;
        foreach ($edges as $edge) {
            if ($edge->getType() === 'has_many') {
                $hasMany = true;
            }
        }
        return $hasMany ? 'has_many' : 'belongs_to';
    }


    /**
     * Generate a subgraph showing all reachable vertices and their relationships
     *
     * @param mixed $startId Starting vertex ID
     * @return Graph A new graph containing only reachable vertices and all their interconnections
     */
    /**
     * Generate a subgraph showing all reachable vertices and their relationships
     *
     * @param Vertex $startVertex Starting vertex
     * @return Graph A new graph containing only reachable vertices and all their interconnections
     * @throws VertexNotFoundException If vertex doesn't exist in graph
     */
    public function generateReachableGraph(Vertex $startVertex): Graph
    {
        if (!$this->hasVertex($startVertex->getId())) {
            throw new VertexNotFoundException("Vertex with ID {$startVertex->getId()} not found");
        }

        // Create new graph with same directionality
        $subgraph = new Graph($this->isDirected());

        // Track reachable vertices using DFS
        $reachable = [];
        $this->findReachableVertices($startVertex->getId(), $reachable);

        // Add all reachable vertices to subgraph
        foreach ($reachable as $vertexId => $value) {
            $vertex = $this->getVertex($vertexId);
            if ($vertex) {
                $subgraph->addVertex($vertex);
            }
        }

        // Add all edges between reachable vertices
        foreach ($reachable as $vertexId => $value) {
            $edges = $this->getNeighbors($vertexId);
            foreach ($edges as $edge) {
                $targetId = $edge->getTarget()->getId();
                if (isset($reachable[$targetId])) {
                    $subgraph->addEdge($edge);
                }
            }
        }

        return $subgraph;
    }

    /**
     * Helper method to find all reachable vertices using DFS
     */
    private function findReachableVertices(mixed $vertexId, array &$reachable): void
    {
        $reachable[$vertexId] = true;

        foreach ($this->getNeighbors($vertexId) as $edge) {
            $targetId = $edge->getTarget()->getId();
            if (!isset($reachable[$targetId])) {
                $this->findReachableVertices($targetId, $reachable);
            }
        }
    }


    public function createExpansionGraph(Vertex $startVertex): Graph
    {
        if (!$this->hasVertex($startVertex->getId())) {
            throw new VertexNotFoundException("Vertex with ID {$startVertex->getId()} not found");
        }

        // Create new directed graph
        $subgraph = new Graph(true);
        $subgraph->addVertex($startVertex);

        // Start DFS from the start vertex
        $this->expandDFS($startVertex->getId(), [], $subgraph);

        return $subgraph;
    }

    private function expandDFS(mixed $currentId, array $currentPath, Graph $subgraph): void
    {
        // Add current vertex to current path
        $currentPath[$currentId] = true;

        // Get all outgoing edges
        $edges = $this->getNeighbors($currentId);
        foreach ($edges as $edge) {
            $targetVertex = $edge->getTarget();
            $targetId = $targetVertex->getId();

            // Skip if target is in our current path (avoid cycles)
            if (isset($currentPath[$targetId])) {
                continue;
            }

            // Add vertex if not in subgraph
            if (!$subgraph->hasVertex($targetId)) {
                $subgraph->addVertex($targetVertex);
            }

            // Add the edge
            $subgraph->addEdge($edge);

            // Recursively explore from target
            $this->expandDFS($targetId, $currentPath, $subgraph);
        }
    }
}
