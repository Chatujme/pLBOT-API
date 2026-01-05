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
 *
 * Authentication: Uses OAuth 2.0 Client Credentials flow (server-side).
 * Rate Limits: Twitch API has rate limits. Responses are cached for 2-10 minutes.
 * Note: Some data (subscriber count, chat viewers) requires user OAuth token.
 */
#[Path('/twitch')]
#[Tag('Twitch')]
final class TwitchController extends BaseController
{
    public function __construct(
        private readonly TwitchService $twitchService
    ) {
    }

    /**
     * Get live streams
     * Returns list of currently live streams sorted by viewer count.
     * Supports filtering by language, game and pagination.
     */
    #[Path('/streams')]
    #[Method('GET')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of streams (1-100, default: 20)')]
    #[RequestParameter(name: 'language', type: 'string', in: 'query', required: false, description: 'Language filter (ISO 639-1, e.g., cs, en, de)')]
    #[RequestParameter(name: 'game_id', type: 'string', in: 'query', required: false, description: 'Filter by game ID')]
    #[RequestParameter(name: 'cursor', type: 'string', in: 'query', required: false, description: 'Pagination cursor for next page')]
    #[ApiResponse(code: '200', description: 'List of live streams with pagination')]
    #[ApiResponse(code: '400', description: 'Invalid request parameters')]
    #[ApiResponse(code: '500', description: 'Internal server error or Twitch API error')]
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

    /**
     * Get Czech live streams
     * Returns list of currently live streams in Czech language, sorted by viewer count.
     */
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

    /**
     * Get top games/categories
     * Returns list of most popular games/categories on Twitch sorted by current viewer count.
     */
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

    /**
     * Search channels
     * Search for channels by name or description. Returns channels that have streamed within the past 6 months.
     */
    #[Path('/search/channels')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Search query (channel name or description)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of results (1-100, default: 20)')]
    #[RequestParameter(name: 'live_only', type: 'bool', in: 'query', required: false, description: 'Show only currently live channels')]
    #[ApiResponse(code: '200', description: 'List of channels matching the search query')]
    #[ApiResponse(code: '400', description: 'Missing or invalid query parameter')]
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

    /**
     * Search games/categories
     * Search for games or categories by name.
     */
    #[Path('/search/games')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Search query (game name)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of results (1-100, default: 20)')]
    #[ApiResponse(code: '200', description: 'List of games/categories matching the search query')]
    #[ApiResponse(code: '400', description: 'Missing or invalid query parameter')]
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

    /**
     * Get user information
     * Returns detailed information about a Twitch user including profile, broadcaster type, and follower count.
     */
    #[Path('/user/{login}')]
    #[Method('GET')]
    #[RequestParameter(name: 'login', type: 'string', in: 'path', required: true, description: 'User login name (lowercase username)')]
    #[ApiResponse(code: '200', description: 'User information with follower count')]
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

    /**
     * Get channel status
     * Returns channel information with live status. If the channel is live, includes current stream details.
     */
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

    /**
     * Get game information
     * Returns information about a specific game/category by name.
     */
    #[Path('/game/{name}')]
    #[Method('GET')]
    #[RequestParameter(name: 'name', type: 'string', in: 'path', required: true, description: 'Game name (exact match)')]
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

    /**
     * Get streams by game
     * Returns list of live streams for a specific game/category.
     */
    #[Path('/game/{game_id}/streams')]
    #[Method('GET')]
    #[RequestParameter(name: 'game_id', type: 'string', in: 'path', required: true, description: 'Game ID (numeric)')]
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

    /**
     * Get channel clips
     * Returns most viewed clips for a channel, sorted by view count.
     */
    #[Path('/channel/{broadcaster_id}/clips')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'path', required: true, description: 'Broadcaster user ID (numeric)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of clips (1-100, default: 20)')]
    #[ApiResponse(code: '200', description: 'List of clips for the broadcaster')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelClips(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');
            $limit = (int) ($request->getParameter('limit') ?? 20);

            $data = $this->twitchService->getClips($broadcasterId, null, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get channel videos (VODs)
     * Returns videos for a channel including archives, highlights, and uploads.
     */
    #[Path('/channel/{broadcaster_id}/videos')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'path', required: true, description: 'Broadcaster user ID (numeric)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of videos (1-100, default: 20)')]
    #[RequestParameter(name: 'type', type: 'string', in: 'query', required: false, description: 'Video type: all, archive, highlight, upload')]
    #[ApiResponse(code: '200', description: 'List of videos for the broadcaster')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelVideos(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');
            $limit = (int) ($request->getParameter('limit') ?? 20);
            $type = $request->getParameter('type') ?? 'all';

            $data = $this->twitchService->getVideos($broadcasterId, $limit, $type);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get channel schedule
     * Returns the streaming schedule for a channel. Returns 404 if no schedule configured.
     */
    #[Path('/channel/{broadcaster_id}/schedule')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'path', required: true, description: 'Broadcaster user ID (numeric)')]
    #[ApiResponse(code: '200', description: 'Channel stream schedule')]
    #[ApiResponse(code: '400', description: 'Channel has no schedule')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelSchedule(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');

            $data = $this->twitchService->getSchedule($broadcasterId);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get channel emotes
     * Returns all subscriber emotes for a channel, organized by tier.
     */
    #[Path('/channel/{broadcaster_id}/emotes')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'path', required: true, description: 'Broadcaster user ID (numeric)')]
    #[ApiResponse(code: '200', description: 'List of channel emotes with image URLs')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelEmotes(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');

            $data = $this->twitchService->getChannelEmotes($broadcasterId);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get channel badges
     * Returns all chat badges for a channel (subscriber badges, bits badges, etc.).
     */
    #[Path('/channel/{broadcaster_id}/badges')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'path', required: true, description: 'Broadcaster user ID (numeric)')]
    #[ApiResponse(code: '200', description: 'List of channel chat badges')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getChannelBadges(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');

            $data = $this->twitchService->getChannelBadges($broadcasterId);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get global emotes
     * Returns all global Twitch emotes available to all users.
     */
    #[Path('/emotes/global')]
    #[Method('GET')]
    #[ApiResponse(code: '200', description: 'List of global Twitch emotes')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getGlobalEmotes(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->twitchService->getGlobalEmotes();
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get cheermotes (bit emotes)
     * Returns available cheermotes with all tiers and image URLs.
     */
    #[Path('/cheermotes')]
    #[Method('GET')]
    #[RequestParameter(name: 'broadcaster_id', type: 'string', in: 'query', required: false, description: 'Optional broadcaster ID for channel-specific cheermotes')]
    #[ApiResponse(code: '200', description: 'List of cheermotes (bit emotes)')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getCheermotes(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $broadcasterId = $request->getParameter('broadcaster_id');

            $data = $this->twitchService->getCheermotes($broadcasterId);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Get game clips
     * Returns most viewed clips for a specific game/category.
     */
    #[Path('/game/{game_id}/clips')]
    #[Method('GET')]
    #[RequestParameter(name: 'game_id', type: 'string', in: 'path', required: true, description: 'Game ID (numeric)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Number of clips (1-100, default: 20)')]
    #[ApiResponse(code: '200', description: 'List of clips for the game')]
    #[ApiResponse(code: '500', description: 'Internal server error')]
    public function getGameClips(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $gameId = $request->getParameter('game_id');
            $limit = (int) ($request->getParameter('limit') ?? 20);

            $data = $this->twitchService->getClips(null, $gameId, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
