<?php

namespace Locospec\Engine;

class MermaidRenderer
{
    /**
     * The CDN URL for Mermaid
     */
    private string $mermaidCdn = 'https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js';

    /**
     * Configure Mermaid settings
     */
    private array $config = [
        'theme' => 'default',
        'securityLevel' => 'loose',
        'startOnLoad' => true,
    ];

    /**
     * Creates a new MermaidRenderer instance with optional configuration
     *
     * @param  array  $config  Optional Mermaid configuration settings
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Converts Mermaid syntax to complete HTML page with proper CDN setup
     *
     * @param  string  $mermaidSyntax  The Mermaid diagram syntax
     * @param  string  $title  Page title
     * @param  string|null  $elementId  Optional custom ID for the diagram element
     * @return string The complete HTML page
     */
    public function render(string $mermaidSyntax, string $title = 'Mermaid Diagram', ?string $elementId = null): string
    {
        // Generate a random ID if none provided
        $elementId = $elementId ?? 'mermaid_'.uniqid();

        // Encode config as JSON
        $configJson = json_encode($this->config);

        // Clean and encode the Mermaid syntax
        $cleanSyntax = htmlspecialchars($mermaidSyntax, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .mermaid {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$title}</h1>
        <div class="mermaid" id="{$elementId}">
            {$cleanSyntax}
        </div>
    </div>
    <script src="{$this->mermaidCdn}"></script>
    <script>
        mermaid.initialize({$configJson});
    </script>
</body>
</html>
HTML;
    }

    /**
     * Sets the Mermaid CDN URL
     *
     * @param  string  $cdnUrl  The CDN URL to use
     */
    public function setCdnUrl(string $cdnUrl): self
    {
        $this->mermaidCdn = $cdnUrl;

        return $this;
    }

    /**
     * Updates Mermaid configuration
     *
     * @param  array  $config  Configuration options to update
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }
}
