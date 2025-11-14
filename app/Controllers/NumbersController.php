<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\NumbersService;

#[Path('/numbers')]
#[Tag('Fun APIs')]
final class NumbersController extends BaseController
{
    public function __construct(
        private readonly NumbersService $numbersService
    ) {
    }

    #[Path('/{number}')]
    #[Method('GET')]
    #[RequestParameter(name: 'number', type: 'string', in: 'path', required: true, description: 'Číslo nebo "random"')]
    #[RequestParameter(name: 'type', type: 'string', in: 'query', required: false, description: 'Typ (trivia, math, year) - výchozí trivia')]
        #[ApiResponse(code: '200', description: 'Zajímavost o čísle')]
    #[ApiResponse(code: '400', description: 'Neplatný typ')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getNumberFact(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $number = $request->getParameter('number');
            $type = $request->getParameter('type') ?? 'trivia';

            // Pokud není 'random', převést na int
            if ($number !== 'random') {
                if (!is_numeric($number)) {
                    return $this->createErrorResponse($response, 'Číslo musí být číslo nebo "random"', 400);
                }
                $number = (int) $number;
            }

            $data = $this->numbersService->getNumberFact($number, $type);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/today')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Zajímavost o dnešním datu')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getTodayFact(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->numbersService->getTodayFact();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
