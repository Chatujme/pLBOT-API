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
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder
    ) {
    }

    #[Path('/spec')]
    #[Method('GET')]
    public function getSpec(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $schema = $this->schemaBuilder->build();

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->writeJsonBody($schema->toArray());
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se vygenerovat OpenAPI specifikaci: ' . $e->getMessage(),
                500
            );
        }
    }
}
