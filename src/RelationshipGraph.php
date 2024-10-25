<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

class RelationshipGraph
{
    /**
     * The underlying graph structure
     */
    private readonly Graph $graph;

    /**
     * Creates a new RelationshipGraph instance
     *
     * @param  Graph  $graph  The underlying graph structure
     */
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Generates a tree structure using Breadth-First Traversal from a start vertex
     *
     * @param  Vertex  $startVertex  The starting vertex
     * @return TreeNode The root node of the generated tree
     *
     * @throws InvalidArgumentException If vertex is not in the graph
     */
    public function generateBFTTree(Vertex $startVertex): TreeNode
    {
        if (! $this->graph->hasVertex($startVertex->getId())) {
            throw new InvalidArgumentException('Start vertex is not in the graph');
        }

        // Initialize the root node and visited set
        $root = new TreeNode($startVertex);
        $visited = [$startVertex->getId() => true];

        // Queue to track vertices to visit along with their parent nodes
        $queue = new \SplQueue;
        $queue->enqueue(['vertex' => $startVertex, 'node' => $root]);

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();
            $currentVertex = $current['vertex'];
            $currentNode = $current['node'];

            // Get and sort neighbors by ID for consistent ordering
            $neighbors = $this->graph->getNeighbors($currentVertex->getId());
            usort($neighbors, function ($a, $b) {
                return strcmp($a->getTarget()->getId(), $b->getTarget()->getId());
            });

            foreach ($neighbors as $edge) {
                $neighborVertex = $edge->getTarget();
                $neighborId = $neighborVertex->getId();

                if (! isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $childNode = new TreeNode($neighborVertex);
                    $currentNode->children[] = $childNode;
                    $queue->enqueue([
                        'vertex' => $neighborVertex,
                        'node' => $childNode,
                    ]);
                }
            }
        }

        return $root;
    }

    /**
     * Generates a tree structure using Depth-First Traversal from a start vertex
     *
     * @param  Vertex  $startVertex  The starting vertex
     * @return TreeNode The root node of the generated tree
     *
     * @throws InvalidArgumentException If vertex is not in the graph
     */
    public function generateDFTTree(Vertex $startVertex): TreeNode
    {
        if (! $this->graph->hasVertex($startVertex->getId())) {
            throw new InvalidArgumentException('Start vertex is not in the graph');
        }

        return $this->dftHelper($startVertex, []);
    }

    /**
     * Helper function for DFT tree generation
     *
     * @param  Vertex  $vertex  Current vertex
     * @param  array  $visited  Set of visited vertex IDs
     * @return TreeNode The generated tree node
     */
    private function dftHelper(Vertex $vertex, array $visited): TreeNode
    {
        $node = new TreeNode($vertex);
        $visited[$vertex->getId()] = true;

        // Get and sort neighbors by ID for consistent ordering
        $neighbors = $this->graph->getNeighbors($vertex->getId());
        usort($neighbors, function ($a, $b) {
            return strcmp($a->getTarget()->getId(), $b->getTarget()->getId());
        });

        foreach ($neighbors as $edge) {
            $neighborVertex = $edge->getTarget();
            if (! isset($visited[$neighborVertex->getId()])) {
                $childNode = $this->dftHelper($neighborVertex, $visited);
                $node->children[] = $childNode;
            }
        }

        return $node;
    }

    /**
     * Converts a traversal tree to Mermaid diagram syntax
     *
     * @param  TreeNode  $root  The root node of the tree
     * @return string The Mermaid diagram syntax
     */
    public function treeToMermaidSyntax(TreeNode $root): string
    {
        $lines = ['graph TD'];
        $edges = [];

        // Helper function to collect all edges
        $collectEdges = function (TreeNode $node) use (&$collectEdges, &$edges) {
            foreach ($node->children as $child) {
                $sourceId = $this->getMermaidSafeId($node->vertex->getId());
                $targetId = $this->getMermaidSafeId($child->vertex->getId());
                $edges[] = "    {$sourceId} --> {$targetId}";
                $collectEdges($child);
            }
        };

        $collectEdges($root);

        // Sort edges for consistent output
        sort($edges);

        return implode("\n", array_merge($lines, array_unique($edges)));
    }

    /**
     * Converts a vertex ID to a Mermaid-safe identifier
     *
     * @param  mixed  $id  The vertex ID
     * @return string Mermaid-safe identifier
     */
    private function getMermaidSafeId(mixed $id): string
    {
        return preg_replace(
            '/[^a-zA-Z0-9_]/',
            '',
            str_replace(' ', '_', (string) $id)
        );
    }
}
