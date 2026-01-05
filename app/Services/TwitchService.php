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
     * Get follower count for a broadcaster
     *
     * @param string $broadcasterId Broadcaster user ID
     * @return int Number of followers
     */
    public function getFollowerCount(string $broadcasterId): int
    {
        $cacheKey = 'twitch_followers_' . $broadcasterId . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/channels/followers', [
            'broadcaster_id' => $broadcasterId,
            'first' => 1,
        ]);

        $count = $data['total'] ?? 0;

        $this->cache->save($cacheKey, $count, [
            Cache::EXPIRE => self::CACHE_STREAMS_EXPIRATION,
        ]);

        return $count;
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

        $user = $data['data'][0];
        $followerCount = $this->getFollowerCount($user['id']);

        $result = [
            'data' => array_merge($this->formatUser($user), ['follower_count' => $followerCount]),
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

        // Get stream info and follower count
        $streamData = $this->twitchRequest('/streams', ['user_id' => $userId]);
        $followerCount = $this->getFollowerCount($userId);

        $isLive = !empty($streamData['data']);
        $stream = $isLive ? $this->formatStream($streamData['data'][0]) : null;

        $result = [
            'data' => [
                'user' => array_merge($this->formatUser($user), ['follower_count' => $followerCount]),
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
     * Get clips for a broadcaster or game
     *
     * @param string|null $broadcasterId Broadcaster ID
     * @param string|null $gameId Game ID
     * @param int $limit Number of clips (1-100)
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getClips(?string $broadcasterId = null, ?string $gameId = null, int $limit = self::DEFAULT_LIMIT): array
    {
        if ($broadcasterId === null && $gameId === null) {
            throw new \RuntimeException('Either broadcaster_id or game_id is required');
        }

        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_clips_' . ($broadcasterId ?? '') . '_' . ($gameId ?? '') . '_' . $limit . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = ['first' => $limit];
        if ($broadcasterId !== null) {
            $params['broadcaster_id'] = $broadcasterId;
        }
        if ($gameId !== null) {
            $params['game_id'] = $gameId;
        }

        $data = $this->twitchRequest('/clips', $params);

        $result = [
            'data' => array_map([$this, 'formatClip'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_STREAMS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get videos for a broadcaster
     *
     * @param string $userId User/broadcaster ID
     * @param int $limit Number of videos
     * @param string $type Video type (all, archive, highlight, upload)
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, string>}
     */
    public function getVideos(string $userId, int $limit = self::DEFAULT_LIMIT, string $type = 'all'): array
    {
        $limit = min(max(1, $limit), self::MAX_LIMIT);

        $cacheKey = 'twitch_videos_' . $userId . '_' . $type . '_' . $limit . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [
            'user_id' => $userId,
            'first' => $limit,
        ];

        if ($type !== 'all') {
            $params['type'] = $type;
        }

        $data = $this->twitchRequest('/videos', $params);

        $result = [
            'data' => array_map([$this, 'formatVideo'], $data['data'] ?? []),
            'pagination' => $data['pagination'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_GAMES_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get channel schedule
     *
     * @param string $broadcasterId Broadcaster ID
     * @return array{data: array<string, mixed>}
     */
    public function getSchedule(string $broadcasterId): array
    {
        $cacheKey = 'twitch_schedule_' . $broadcasterId . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/schedule', ['broadcaster_id' => $broadcasterId]);

        $result = [
            'data' => $data['data'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_USERS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get channel emotes
     *
     * @param string $broadcasterId Broadcaster ID
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function getChannelEmotes(string $broadcasterId): array
    {
        $cacheKey = 'twitch_emotes_' . $broadcasterId . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/chat/emotes', ['broadcaster_id' => $broadcasterId]);

        $result = [
            'data' => array_map([$this, 'formatEmote'], $data['data'] ?? []),
            'template' => $data['template'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_USERS_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Get global Twitch emotes
     *
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function getGlobalEmotes(): array
    {
        $cacheKey = 'twitch_global_emotes_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/chat/emotes/global');

        $result = [
            'data' => array_map([$this, 'formatEmote'], $data['data'] ?? []),
            'template' => $data['template'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => '1 hour',
        ]);

        return $result;
    }

    /**
     * Get cheermotes (bit emotes)
     *
     * @param string|null $broadcasterId Optional broadcaster ID
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function getCheermotes(?string $broadcasterId = null): array
    {
        $cacheKey = 'twitch_cheermotes_' . ($broadcasterId ?? 'global') . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = [];
        if ($broadcasterId !== null) {
            $params['broadcaster_id'] = $broadcasterId;
        }

        $data = $this->twitchRequest('/bits/cheermotes', $params);

        $result = [
            'data' => $data['data'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => '1 hour',
        ]);

        return $result;
    }

    /**
     * Get chat badges for a channel
     *
     * @param string $broadcasterId Broadcaster ID
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function getChannelBadges(string $broadcasterId): array
    {
        $cacheKey = 'twitch_badges_' . $broadcasterId . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->twitchRequest('/chat/badges', ['broadcaster_id' => $broadcasterId]);

        $result = [
            'data' => $data['data'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_USERS_EXPIRATION,
        ]);

        return $result;
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

    /**
     * Format clip data for API response
     */
    private function formatClip(array $clip): array
    {
        return [
            'id' => $clip['id'] ?? null,
            'url' => $clip['url'] ?? null,
            'embed_url' => $clip['embed_url'] ?? null,
            'broadcaster_id' => $clip['broadcaster_id'] ?? null,
            'broadcaster_name' => $clip['broadcaster_name'] ?? null,
            'creator_id' => $clip['creator_id'] ?? null,
            'creator_name' => $clip['creator_name'] ?? null,
            'video_id' => $clip['video_id'] ?? null,
            'game_id' => $clip['game_id'] ?? null,
            'language' => $clip['language'] ?? null,
            'title' => $clip['title'] ?? null,
            'view_count' => $clip['view_count'] ?? 0,
            'created_at' => $clip['created_at'] ?? null,
            'thumbnail_url' => $clip['thumbnail_url'] ?? null,
            'duration' => $clip['duration'] ?? 0,
            'vod_offset' => $clip['vod_offset'] ?? null,
        ];
    }

    /**
     * Format video data for API response
     */
    private function formatVideo(array $video): array
    {
        return [
            'id' => $video['id'] ?? null,
            'stream_id' => $video['stream_id'] ?? null,
            'user_id' => $video['user_id'] ?? null,
            'user_login' => $video['user_login'] ?? null,
            'user_name' => $video['user_name'] ?? null,
            'title' => $video['title'] ?? null,
            'description' => $video['description'] ?? null,
            'created_at' => $video['created_at'] ?? null,
            'published_at' => $video['published_at'] ?? null,
            'url' => $video['url'] ?? null,
            'thumbnail_url' => isset($video['thumbnail_url'])
                ? str_replace(['%{width}', '%{height}'], ['320', '180'], $video['thumbnail_url'])
                : null,
            'viewable' => $video['viewable'] ?? null,
            'view_count' => $video['view_count'] ?? 0,
            'language' => $video['language'] ?? null,
            'type' => $video['type'] ?? null,
            'duration' => $video['duration'] ?? null,
            'muted_segments' => $video['muted_segments'] ?? [],
        ];
    }

    /**
     * Format emote data for API response
     */
    private function formatEmote(array $emote): array
    {
        return [
            'id' => $emote['id'] ?? null,
            'name' => $emote['name'] ?? null,
            'images' => $emote['images'] ?? [],
            'tier' => $emote['tier'] ?? null,
            'emote_type' => $emote['emote_type'] ?? null,
            'emote_set_id' => $emote['emote_set_id'] ?? null,
            'format' => $emote['format'] ?? [],
            'scale' => $emote['scale'] ?? [],
            'theme_mode' => $emote['theme_mode'] ?? [],
        ];
    }
}
