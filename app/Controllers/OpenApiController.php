<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\OpenApi\SchemaBuilder;

#[Path('/openapi')]
#[Tag('Documentation')]
final class OpenApiController extends BaseController
{
    private const EXAMPLES_FILE = __DIR__ . '/../config/openapi-examples.php';

    public function __construct(
        private readonly SchemaBuilder $schemaBuilder
    ) {
    }

    /** @var string[] Paths to exclude from OpenAPI spec */
    private const EXCLUDED_PATHS = ['/admin', '/openapi'];

    #[Path('/spec')]
    #[Method('GET')]
    public function getSpec(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $schema = $this->schemaBuilder->build();
            $schemaArray = $schema->toArray();

            // Filter out internal endpoints
            $schemaArray = $this->filterPaths($schemaArray);

            // Load and merge examples
            $schemaArray = $this->mergeExamples($schemaArray);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->writeJsonBody($schemaArray);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se vygenerovat OpenAPI specifikaci: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Filter out internal endpoints from OpenAPI spec.
     */
    private function filterPaths(array $schema): array
    {
        if (!isset($schema['paths'])) {
            return $schema;
        }

        $filteredPaths = [];
        foreach ($schema['paths'] as $path => $methods) {
            $exclude = false;
            foreach (self::EXCLUDED_PATHS as $excludedPath) {
                if (str_starts_with($path, $excludedPath)) {
                    $exclude = true;
                    break;
                }
            }
            if (!$exclude) {
                $filteredPaths[$path] = $methods;
            }
        }

        $schema['paths'] = $filteredPaths;

        // Also filter tags
        if (isset($schema['tags'])) {
            $schema['tags'] = array_values(array_filter($schema['tags'], function ($tag) {
                return !in_array($tag['name'] ?? '', ['Admin', 'Documentation']);
            }));
        }

        return $schema;
    }

    /**
     * Merge examples from external file into OpenAPI schema.
     */
    private function mergeExamples(array $schema): array
    {
        if (!file_exists(self::EXAMPLES_FILE)) {
            return $schema;
        }

        $examples = require self::EXAMPLES_FILE;

        if (!is_array($examples) || !isset($schema['paths'])) {
            return $schema;
        }

        foreach ($examples as $path => $methods) {
            if (!isset($schema['paths'][$path])) {
                continue;
            }

            foreach ($methods as $method => $example) {
                $method = strtolower($method);
                if (!isset($schema['paths'][$path][$method])) {
                    continue;
                }

                // Add example to 200 response
                if (!isset($schema['paths'][$path][$method]['responses']['200'])) {
                    $schema['paths'][$path][$method]['responses']['200'] = [
                        'description' => 'Successful response',
                    ];
                }

                $schema['paths'][$path][$method]['responses']['200']['content'] = [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                        ],
                        'example' => $example,
                    ],
                ];
            }
        }

        return $schema;
    }
}
