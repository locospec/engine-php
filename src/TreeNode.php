<?php

namespace Locospec\EnginePhp;

class TreeNode
{
    public readonly Vertex $vertex;

    public array $children = [];

    public function __construct(Vertex $vertex)
    {
        $this->vertex = $vertex;
    }

    /**
     * Convert the tree structure to an associative array
     *
     * @return array{
     *    id: mixed,
     *    children: array<array{id: mixed, children: array}>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->vertex->getId(),
            'children' => array_map(
                fn (TreeNode $child) => $child->toArray(),
                $this->children
            ),
        ];
    }

    /**
     * Convert tree to Mermaid diagram syntax
     */
    public function toMermaidSyntax(): string
    {
        $lines = ['graph TD'];
        $visited = [];

        $this->traverse($this, $lines, $visited);

        return implode("\n", $lines);
    }

    /**
     * Check if a path exists between current node and target vertex ID
     */
    public function hasPath(mixed $targetId): bool
    {
        // Base case: current node is target
        if ($this->vertex->getId() === $targetId) {
            return true;
        }

        // Recursively check children
        foreach ($this->children as $child) {
            if ($child->hasPath($targetId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find shortest path to target vertex ID
     *
     * @param  mixed  $targetId  The target vertex ID to find path to
     * @return array<mixed>|null Array of vertex IDs representing path, or null if no path exists
     */
    public function findPath(mixed $targetId): ?array
    {
        // Base case: current node is target
        if ($this->vertex->getId() === $targetId) {
            return [$this->vertex->getId()];
        }

        // BFS implementation
        $queue = new \SplQueue;
        $queue->enqueue([
            'node' => $this,
            'path' => [$this->vertex->getId()],
        ]);

        $visited = [$this->vertex->getId() => true];

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();
            /** @var TreeNode */
            $currentNode = $current['node'];
            $currentPath = $current['path'];

            foreach ($currentNode->children as $child) {
                $childId = $child->vertex->getId();

                if ($childId === $targetId) {
                    // Found the target - return the complete path
                    return array_merge($currentPath, [$childId]);
                }

                if (! isset($visited[$childId])) {
                    $visited[$childId] = true;
                    $queue->enqueue([
                        'node' => $child,
                        'path' => array_merge($currentPath, [$childId]),
                    ]);
                }
            }
        }

        return null;
    }

    private function traverse(TreeNode $node, array &$lines, array &$visited): void
    {
        $nodeId = $this->getMermaidSafeId($node->vertex->getId());

        if (in_array($nodeId, $visited)) {
            return;
        }

        $visited[] = $nodeId;

        foreach ($node->children as $child) {
            $childId = $this->getMermaidSafeId($child->vertex->getId());
            $lines[] = "    {$nodeId} --> {$childId}";
            $this->traverse($child, $lines, $visited);
        }
    }

    private function getMermaidSafeId(mixed $id): string
    {
        return preg_replace(
            '/[^a-zA-Z0-9_]/',
            '',
            str_replace(' ', '_', (string) $id)
        );
    }

    /**
     * Reconstruct path from parents map
     *
     * @param  array<mixed, mixed>  $parents  Map of child ID to parent ID
     * @param  mixed  $startId  Starting vertex ID
     * @param  mixed  $endId  Ending vertex ID
     * @return array<mixed> Array of vertex IDs representing the path
     */
    private function reconstructPath(array $parents, mixed $startId, mixed $endId): array
    {
        $path = [$endId];
        $current = $endId;

        // Work backwards from end to start
        while ($current !== $startId) {
            $current = $parents[$current];
            array_unshift($path, $current);
        }

        return $path;
    }
}
