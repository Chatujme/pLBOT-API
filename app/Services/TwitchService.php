<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service for Twitch API integration
 * Provides access to streams, channels, games, and user data from Twitch
 */
final class TwitchService
{
    private Cache $cache;

    private const TWITCH_API_URL = 'https://api.twitch.tv/helix';
    private const TWITCH_AUTH_URL = 'https://id.twitch.tv/oauth2/token';

    private const CACHE_TOKEN_EXPIRATION = '1 hour';
    private const CACHE_STREAMS_EXPIRATION = '2 minutes';
    private const CACHE_GAMES_EXPIRATION = '5 minutes';
    private const CACHE_USERS_EXPIRATION = '10 minutes';

    private const CZECH_LANGUAGE = 'cs';
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    private ?string $accessToken = null;
    private string $clientId;
    private string $clientSecret;

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);

        // Load credentials from environment or config file
        $this->clientId = $_ENV['TWITCH_CLIENT_ID'] ?? getenv('TWITCH_CLIENT_ID') ?: '';
        $this->clientSecret = $_ENV['TWITCH_CLIENT_SECRET'] ?? getenv('TWITCH_CLIENT_SECRET') ?: '';

        // Try to load from local config file if env vars not set
        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->loadCredentialsFromConfig();
        }
    }

    /**
     * Load Twitch credentials from config file
     */
    private function loadCredentialsFromConfig(): void
    {
        $configFile = __DIR__ . '/../config/twitch.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['client_id'])) {
                $this->clientId = $config['client_id'];
            }
            if (isset($config['client_secret'])) {
                $this->clientSecret = $config['client_secret'];
            }
        }
    }

    /**
     * Get OAuth app access token from Twitch
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $cacheKey = 'twitch_access_token';
        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            $this->accessToken = $cached;
            return $this->accessToken;
        }

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException('Twitch API credentials not configured. Set TWITCH_CLIENT_ID and TWITCH_CLIENT_SECRET.');
        }

        try {
            $postData = http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            $response = $this->httpClient->post(self::TWITCH_AUTH_URL, $postData, [
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $data = json_decode($response, true);

            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Failed to obtain Twitch access token');
            }

            $this->accessToken = $data['access_token'];

            // Cache token (expires_in is typically ~5 million seconds, we cache for 1 hour)
            $this->cache->save($cacheKey, $this->accessToken, [
                Cache::EXPIRE => self::CACHE_TOKEN_EXPIRATION,
            ]);

            return $this->accessToken;
        } catch (\Exception $e) {
            throw new \RuntimeException("Twitch authentication failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Make authenticated request to Twitch API
     */
    private function twitchRequest(string $endpoint, array $params = []): array
    {
        $token = $this->getAccessToken();

        $url = self::TWITCH_API_URL . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Authorization: Bearer ' . $token,
                    'Client-Id: ' . $this->clientId,
                ],
            ]);

            return json_decode($response, true) ?? [];
        } catch (\Exception $e) {
            // If unauthorized, clear token cache and retry once
            if (str_contains($e->getMessage(), '401')) {
                $this->cache->remove('twitch_access_token');
                $this->accessToken = null;
                return $this->twitchRequest($endpoint, $params);
            }
            throw new \RuntimeException("Twitch API request failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get live streams
     *
     * @param int $limit Number of streams (1-100)
     * @param string|null $language Filter by language (e.g., 'cs' for Czech)
     * @param string|null $gameId Filter by game ID
     * @param string|null $cursor Pagination cursor
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getStreams(int $limit = self::DEFAULT_LIMIT, ?string $language = null, ?string $gameId = null, ?string $cursor = null): array
    {
        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_streams_' . md5(implode('_', [$limit, $language ?? '', $gameId ?? '', $cursor ?? ''])) . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [
            'first' => $limit,
            'type' => 'live',
        ];

        if ($language !== null) {
            $params['language'] = $language;
        }

        if ($gameId !== null) {
            $params['game_id'] = $gameId;
        }

        if ($cursor !== null) {
            $params['after'] = $cursor;
        }

        $data = $this->twitchRequest('/streams', $params);

        $result = [
            'data' => array_map([$this, 'formatStream'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_STREAMS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get Czech live streams
     *
     * @param int $limit Number of streams
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getCzechStreams(int $limit = self::DEFAULT_LIMIT): array
    {
        return $this->getStreams($limit, self::CZECH_LANGUAGE);
    }

    /**
     * Get top games/categories on Twitch
     *
     * @param int $limit Number of games (1-100)
     * @param string|null $cursor Pagination cursor
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getTopGames(int $limit = self::DEFAULT_LIMIT, ?string $cursor = null): array
    {
        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_top_games_' . $limit . '_' . ($cursor ?? 'first') . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = ['first' => $limit];
        if ($cursor !== null) {
            $params['after'] = $cursor;
        }

        $data = $this->twitchRequest('/games/top', $params);

        $result = [
            'data' => array_map([$this, 'formatGame'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_GAMES_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Search for channels
     *
     * @param string $query Search query
     * @param int $limit Number of results
     * @param bool $liveOnly Show only live channels
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function searchChannels(string $query, int $limit = self::DEFAULT_LIMIT, bool $liveOnly = false): array
    {
        if (empty($query)) {
            throw new \RuntimeException('Search query cannot be empty');
        }

        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_search_channels_' . md5($query) . '_' . $limit . '_' . ($liveOnly ? '1' : '0') . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [
            'query' => $query,
            'first' => $limit,
        ];

        if ($liveOnly) {
            $params['live_only'] = 'true';
        }

        $data = $this->twitchRequest('/search/channels', $params);

        $result = [
            'data' => array_map([$this, 'formatChannel'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'query' => $query,
            'live_only' => $liveOnly,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_STREAMS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Search for games/categories
     *
     * @param string $query Search query
     * @param int $limit Number of results
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function searchGames(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        if (empty($query)) {
            throw new \RuntimeException('Search query cannot be empty');
        }

        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_search_games_' . md5($query) . '_' . $limit . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [
            'query' => $query,
            'first' => $limit,
        ];

        $data = $this->twitchRequest('/search/categories', $params);

        $result = [
            'data' => array_map([$this, 'formatGame'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'query' => $query,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_GAMES_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get user/channel information by login name
     *
     * @param string $login User login name
     * @return array{data: array<string, mixed>}
     */
    public function getUserByLogin(string $login): array
    {
        if (empty($login)) {
            throw new \RuntimeException('Login name cannot be empty');
        }

        $cacheKey = 'twitch_user_' . strtolower($login) . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/users', ['login' => $login]);

        if (empty($data['data'])) {
            throw new \RuntimeException("User '{$login}' not found on Twitch");
        }

        $result = [
            'data' => $this->formatUser($data['data'][0]),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_USERS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get channel stream status (is live + stream info)
     *
     * @param string $login User login name
     * @return array{data: array<string, mixed>}
     */
    public function getChannelStatus(string $login): array
    {
        if (empty($login)) {
            throw new \RuntimeException('Login name cannot be empty');
        }

        $cacheKey = 'twitch_status_' . strtolower($login) . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Get user info first
        $userData = $this->twitchRequest('/users', ['login' => $login]);

        if (empty($userData['data'])) {
            throw new \RuntimeException("Channel '{$login}' not found on Twitch");
        }

        $user = $userData['data'][0];
        $userId = $user['id'];

        // Get stream info
        $streamData = $this->twitchRequest('/streams', ['user_id' => $userId]);

        $isLive = !empty($streamData['data']);
        $stream = $isLive ? $this->formatStream($streamData['data'][0]) : null;

        $result = [
            'data' => [
                'user' => $this->formatUser($user),
                'is_live' => $isLive,
                'stream' => $stream,
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_STREAMS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get streams for a specific game
     *
     * @param string $gameId Game ID
     * @param int $limit Number of streams
     * @param string|null $language Filter by language
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getStreamsByGame(string $gameId, int $limit = self::DEFAULT_LIMIT, ?string $language = null): array
    {
        return $this->getStreams($limit, $language, $gameId);
    }

    /**
     * Get game info by name (search and get first result)
     *
     * @param string $gameName Game name
     * @return array{data: array<string, mixed>}
     */
    public function getGameByName(string $gameName): array
    {
        if (empty($gameName)) {
            throw new \RuntimeException('Game name cannot be empty');
        }

        $cacheKey = 'twitch_game_' . md5(strtolower($gameName)) . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/games', ['name' => $gameName]);

        if (empty($data['data'])) {
            throw new \RuntimeException("Game '{$gameName}' not found on Twitch");
        }

        $result = [
            'data' => $this->formatGame($data['data'][0]),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_GAMES_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Format stream data for API response
     */
    private function formatStream(array $stream): array
    {
        return [
            'id' => $stream['id'] ?? null,
            'user_id' => $stream['user_id'] ?? null,
            'user_login' => $stream['user_login'] ?? null,
            'user_name' => $stream['user_name'] ?? null,
            'game_id' => $stream['game_id'] ?? null,
            'game_name' => $stream['game_name'] ?? null,
            'type' => $stream['type'] ?? null,
            'title' => $stream['title'] ?? null,
            'viewer_count' => $stream['viewer_count'] ?? 0,
            'started_at' => $stream['started_at'] ?? null,
            'language' => $stream['language'] ?? null,
            'thumbnail_url' => isset($stream['thumbnail_url'])
                ? str_replace(['{width}', '{height}'], ['440', '248'], $stream['thumbnail_url'])
                : null,
            'tags' => $stream['tags'] ?? [],
            'is_mature' => $stream['is_mature'] ?? false,
        ];
    }

    /**
     * Format game data for API response
     */
    private function formatGame(array $game): array
    {
        return [
            'id' => $game['id'] ?? null,
            'name' => $game['name'] ?? null,
            'box_art_url' => isset($game['box_art_url'])
                ? str_replace(['{width}', '{height}'], ['285', '380'], $game['box_art_url'])
                : null,
            'igdb_id' => $game['igdb_id'] ?? null,
        ];
    }

    /**
     * Format channel data for API response (search result)
     */
    private function formatChannel(array $channel): array
    {
        return [
            'id' => $channel['id'] ?? null,
            'broadcaster_login' => $channel['broadcaster_login'] ?? null,
            'display_name' => $channel['display_name'] ?? null,
            'game_id' => $channel['game_id'] ?? null,
            'game_name' => $channel['game_name'] ?? null,
            'is_live' => $channel['is_live'] ?? false,
            'title' => $channel['title'] ?? null,
            'started_at' => $channel['started_at'] ?? null,
            'broadcaster_language' => $channel['broadcaster_language'] ?? null,
            'thumbnail_url' => $channel['thumbnail_url'] ?? null,
            'tags' => $channel['tags'] ?? [],
        ];
    }

    /**
     * Format user data for API response
     */
    private function formatUser(array $user): array
    {
        return [
            'id' => $user['id'] ?? null,
            'login' => $user['login'] ?? null,
            'display_name' => $user['display_name'] ?? null,
            'type' => $user['type'] ?? null,
            'broadcaster_type' => $user['broadcaster_type'] ?? null,
            'description' => $user['description'] ?? null,
            'profile_image_url' => $user['profile_image_url'] ?? null,
            'offline_image_url' => $user['offline_image_url'] ?? null,
            'view_count' => $user['view_count'] ?? 0,
            'created_at' => $user['created_at'] ?? null,
        ];
    }
}
