<?php

namespace Locospec\EnginePhp;

class BFTGraph extends TraversalGraph
{
    public function generateTree(Vertex $startVertex): TreeNode
    {
        $this->validateVertex($startVertex);

        // Create root node
        $root = new TreeNode($startVertex);

        // Track visited vertices globally to ensure each vertex appears once
        $visited = [$startVertex->getId() => true];

        // Queue to track vertex and its corresponding tree node
        $queue = new \SplQueue;
        $queue->enqueue([
            'vertex' => $startVertex,
            'node' => $root,
        ]);

        while (! $queue->isEmpty()) {
            $current = $queue->dequeue();
            $currentVertex = $current['vertex'];
            $currentNode = $current['node'];

            // Get and sort neighbors for consistent ordering
            $neighbors = $this->graph->getNeighbors($currentVertex->getId());
            usort($neighbors, function ($a, $b) {
                return strcmp($a->getTarget()->getId(), $b->getTarget()->getId());
            });

            // Process each unvisited neighbor
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
}
