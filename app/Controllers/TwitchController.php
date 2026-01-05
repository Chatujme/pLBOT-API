<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\TwitchService;

/**
 * Controller for Twitch API endpoints
 * Provides access to streams, channels, games, and user data from Twitch
 */
#[Path('/twitch')]
#[Tag('Twitch')]
final class TwitchController extends BaseController
{
    public function __construct(
        private readonly TwitchService $twitchService
    ) {
    }

    #[Path('/streams')]
    #[Method('GET')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of streams (1-100, default: 20)')]
    #[RequestParameter(name: 'language', type: 'string', in: 'query', required: false, description: 'Language filter (ISO 639-1, e.g., cs, en)')]
    #[RequestParameter(name: 'game_id', type: 'string', in: 'query', required: false, description: 'Filter by game ID')]
    #[RequestParameter(name: 'cursor', type: 'string', in: 'query', required: false, description: 'Pagination cursor')]
    #[ApiResponse(code: '200', description: 'List of live streams')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getStreams(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $limit = (int) ($request->getParameter('limit') ?? 20);
            $language = $request->getParameter('language');
            $gameId = $request->getParameter('game_id');
            $cursor = $request->getParameter('cursor');

            $data = $this->twitchService->getStreams($limit, $language, $gameId, $cursor);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/streams/czech')]
    #[Method('GET')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of streams (1-100, default: 20)')]
    #[ApiResponse(code: '200', description: 'List of Czech live streams')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getCzechStreams(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $limit = (int) ($request->getParameter('limit') ?? 20);

            $data = $this->twitchService->getCzechStreams($limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/games/top')]
    #[Method('GET')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of games (1-100, default: 20)')]
    #[RequestParameter(name: 'cursor', type: 'string', in: 'query', required: false, description: 'Pagination cursor')]
    #[ApiResponse(code: '200', description: 'List of top games/categories on Twitch')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getTopGames(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $limit = (int) ($request->getParameter('limit') ?? 20);
            $cursor = $request->getParameter('cursor');

            $data = $this->twitchService->getTopGames($limit, $cursor);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/search/channels')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Search query')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of results (1-100, default: 20)')]
    #[RequestParameter(name: 'live_only', type: 'bool', in: 'query', required: false, description: 'Show only live channels')]
    #[ApiResponse(code: '200', description: 'List of channels matching the search query')]
    #[ApiResponse(code: '400', description: 'Invalid request')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function searchChannels(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $query = $request->getParameter('query');
            if (empty($query)) {
                return $this->createErrorResponse($response, 'Query parameter is required', 400);
            }

            $limit = (int) ($request->getParameter('limit') ?? 20);
            $liveOnly = filter_var($request->getParameter('live_only') ?? false, FILTER_VALIDATE_BOOLEAN);

            $data = $this->twitchService->searchChannels($query, $limit, $liveOnly);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/search/games')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Search query')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of results (1-100, default: 20)')]
    #[ApiResponse(code: '200', description: 'List of games/categories matching the search query')]
    #[ApiResponse(code: '400', description: 'Invalid request')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function searchGames(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $query = $request->getParameter('query');
            if (empty($query)) {
                return $this->createErrorResponse($response, 'Query parameter is required', 400);
            }

            $limit = (int) ($request->getParameter('limit') ?? 20);

            $data = $this->twitchService->searchGames($query, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/user/{login}')]
    #[Method('GET')]
    #[RequestParameter(name: 'login', type: 'string', in: 'path', required: true, description: 'User login name')]
    #[ApiResponse(code: '200', description: 'User information')]
    #[ApiResponse(code: '404', description: 'User not found')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getUser(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $login = $request->getParameter('login');

            $data = $this->twitchService->getUserByLogin($login);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/channel/{login}/status')]
    #[Method('GET')]
    #[RequestParameter(name: 'login', type: 'string', in: 'path', required: true, description: 'Channel login name')]
    #[ApiResponse(code: '200', description: 'Channel status with live stream info if online')]
    #[ApiResponse(code: '404', description: 'Channel not found')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelStatus(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $login = $request->getParameter('login');

            $data = $this->twitchService->getChannelStatus($login);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/game/{name}')]
    #[Method('GET')]
    #[RequestParameter(name: 'name', type: 'string', in: 'path', required: true, description: 'Game name')]
    #[ApiResponse(code: '200', description: 'Game information')]
    #[ApiResponse(code: '404', description: 'Game not found')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getGame(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $name = $request->getParameter('name');

            $data = $this->twitchService->getGameByName($name);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/game/{game_id}/streams')]
    #[Method('GET')]
    #[RequestParameter(name: 'game_id', type: 'string', in: 'path', required: true, description: 'Game ID')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of streams (1-100, default: 20)')]
    #[RequestParameter(name: 'language', type: 'string', in: 'query', required: false, description: 'Language filter (ISO 639-1)')]
    #[ApiResponse(code: '200', description: 'List of streams for the specified game')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getGameStreams(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $gameId = $request->getParameter('game_id');
            $limit = (int) ($request->getParameter('limit') ?? 20);
            $language = $request->getParameter('language');

            $data = $this->twitchService->getStreamsByGame($gameId, $limit, $language);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
