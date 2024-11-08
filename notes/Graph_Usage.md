### Usage

```php
function createCitiesGraph(): Graph
{
    $graph = new Graph(true); // Using directed graph for this example

    $vertices = [
        'Mumbai' => new Vertex('Mumbai'),
        'Dubai' => new Vertex('Dubai'),
        'London' => new Vertex('London'),
        'New York' => new Vertex('New York'),
        'Singapore' => new Vertex('Singapore'),
        'Tokyo' => new Vertex('Tokyo'),
    ];

    // Add vertices to graph
    foreach ($vertices as $vertex) {
        $graph->addVertex($vertex);
    }

    // Define one-way connections
    $connections = [
        'Mumbai' => ['Dubai', 'Singapore'],
        'Dubai' => ['London', 'Singapore'],
        'London' => ['New York', 'Singapore'],
        'Singapore' => ['Tokyo']
    ];

    // Add directed edges
    foreach ($connections as $source => $targets) {
        foreach ($targets as $target) {
            $edge = new Edge($vertices[$source], $vertices[$target]);
            $graph->addEdge($edge);
        }
    }

    return $graph;
}

Route::get('/test', function (Request $request) {
    $enginePHPClass = new EnginePhpClass();
    $result = $enginePHPClass->add(1, 2);

    $graph = createCitiesGraph();
    $renderer = new MermaidRenderer();
    $html = $renderer->render(
        $graph->toMermaidSyntax(),
        'Cities Connection Graph'
    );

    $startVertex = $graph->getVertex('Mumbai');

    // return $html;

    $dftGraph = new DFTGraph($graph);
    $dftTree = $dftGraph->generateTree($startVertex);
    $dfhHtml = $renderer->render(
        $dftTree->toMermaidSyntax(),
        'Mumbai to Cities'
    );

    $bftGraph = new BFTGraph($graph);
    $bftTree = $bftGraph->generateTree($startVertex);
    $bftHtml = $renderer->render(
        $bftTree->toMermaidSyntax(),
        'Mumbai to Cities'
    );

    return $bftHtml;

    return ['status' => 'ok', 'add' => $result];
});
```
