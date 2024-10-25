<?php

namespace Locospec\EnginePhp;

use Locospec\EnginePhp\Exceptions\InvalidArgumentException;

abstract class TraversalGraph
{
    protected readonly Graph $graph;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Generate a tree structure from the graph starting at given vertex
     *
     * @param Vertex $startVertex The vertex to start traversal from
     * @return TreeNode Root node of the generated tree
     * @throws InvalidArgumentException If vertex is not in the graph
     */
    abstract public function generateTree(Vertex $startVertex): TreeNode;

    /**
     * Converts tree to Mermaid diagram syntax
     */
    public function treeToMermaidSyntax(TreeNode $root): string
    {
        $lines = ['graph TD'];
        $this->buildMermaidSyntax($root, $lines);
        return implode("\n", $lines);
    }

    /**
     * Helper to build Mermaid syntax lines recursively
     */
    protected function buildMermaidSyntax(TreeNode $node, array &$lines): void
    {
        $sourceId = $this->getMermaidSafeId($node->vertex->getId());

        foreach ($node->children as $child) {
            $targetId = $this->getMermaidSafeId($child->vertex->getId());
            $lines[] = "    {$sourceId} --> {$targetId}";
            $this->buildMermaidSyntax($child, $lines);
        }
    }

    /**
     * Convert vertex ID to Mermaid-safe identifier
     */
    protected function getMermaidSafeId(mixed $id): string
    {
        return preg_replace(
            '/[^a-zA-Z0-9_]/',
            '',
            str_replace(' ', '_', (string) $id)
        );
    }

    /**
     * Validate vertex exists in graph
     *
     * @throws InvalidArgumentException If vertex not in graph
     */
    protected function validateVertex(Vertex $vertex): void
    {
        if (!$this->graph->hasVertex($vertex->getId())) {
            throw new InvalidArgumentException('Vertex is not present in the graph');
        }
    }
}
