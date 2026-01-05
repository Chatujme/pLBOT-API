<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * AI Service for Groq and Google Gemini API integration.
 */
final class AiService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const GROQ_MODELS = [
        'llama-3.3-70b-versatile' => 'Llama 3.3 70B - nejlepší kvalita',
        'llama-3.1-8b-instant' => 'Llama 3.1 8B - rychlý',
        'mixtral-8x7b-32768' => 'Mixtral 8x7B - vyvážený',
        'gemma2-9b-it' => 'Gemma 2 9B - kompaktní',
    ];

    private const GEMINI_MODELS = [
        'gemini-2.0-flash' => 'Gemini 2.0 Flash - rychlý a versatilní',
        'gemini-2.5-flash' => 'Gemini 2.5 Flash - multimodální, 1M tokenů',
        'gemini-2.5-pro' => 'Gemini 2.5 Pro - nejlepší kvalita',
    ];

    private const OPENROUTER_DEFAULT_MODEL = 'nex-agi/deepseek-v3.1-nex-n1:free';

    private Cache $cache;
    private ?string $groqApiKey = null;
    private ?string $geminiApiKey = null;
    private ?string $openrouterApiKey = null;

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, 'ai');
        $this->loadApiKeys();
    }

    private function loadApiKeys(): void
    {
        $configFile = __DIR__ . '/../config/ai.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $this->groqApiKey = $config['groq_api_key'] ?? null;
            $this->geminiApiKey = $config['gemini_api_key'] ?? null;
            $this->openrouterApiKey = $config['openrouter_api_key'] ?? null;
        }
    }

    /**
     * Get available AI providers and their models.
     */
    public function getProviders(): array
    {
        return [
            'groq' => [
                'name' => 'Groq',
                'description' => 'Ultra-fast inference with open-source models',
                'available' => $this->groqApiKey !== null,
                'models' => self::GROQ_MODELS,
                'default_model' => 'llama-3.3-70b-versatile',
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'description' => 'Google\'s multimodal AI model',
                'available' => $this->geminiApiKey !== null,
                'models' => self::GEMINI_MODELS,
                'default_model' => 'gemini-2.0-flash',
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'description' => 'Unified API for multiple AI models including DeepSeek',
                'available' => $this->openrouterApiKey !== null,
                'models' => $this->getOpenRouterFreeModels(),
                'default_model' => self::OPENROUTER_DEFAULT_MODEL,
            ],
        ];
    }

    /**
     * Get free models from OpenRouter API (cached for 1 hour).
     */
    public function getOpenRouterFreeModels(): array
    {
        $cacheKey = 'openrouter_free_models';

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $responseStr = $this->httpClient->get('https://openrouter.ai/api/v1/models');
            $response = json_decode($responseStr, true);

            if (!is_array($response) || !isset($response['data']) || !is_array($response['data'])) {
                return $this->getOpenRouterFallbackModels();
            }

            $freeModels = [];
            foreach ($response['data'] as $model) {
                // Check if model is free (pricing is 0 for both prompt and completion)
                if (isset($model['id'], $model['name'], $model['pricing'])) {
                    $promptPrice = (float) ($model['pricing']['prompt'] ?? 1);
                    $completionPrice = (float) ($model['pricing']['completion'] ?? 1);

                    if ($promptPrice == 0 && $completionPrice == 0) {
                        $freeModels[$model['id']] = $model['name'];
                    }
                }
            }

            // Sort by model ID for consistency
            ksort($freeModels);

            // Cache for 1 hour
            $this->cache->save($cacheKey, $freeModels, [
                Cache::Expire => '1 hour',
            ]);

            return $freeModels;
        } catch (\Exception $e) {
            return $this->getOpenRouterFallbackModels();
        }
    }

    /**
     * Fallback models if API fails.
     */
    private function getOpenRouterFallbackModels(): array
    {
        return [
            'nex-agi/deepseek-v3.1-nex-n1:free' => 'DeepSeek V3.1 Nex N1 (free)',
            'deepseek/deepseek-r1-0528:free' => 'DeepSeek R1 0528 (free)',
            'mistralai/devstral-2512:free' => 'Mistral Devstral 2 (free)',
        ];
    }

    /**
     * Chat with AI using specified provider.
     */
    public function chat(
        string $message,
        string $provider = 'groq',
        ?string $model = null,
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1024
    ): array {
        if ($provider === 'groq') {
            return $this->chatGroq($message, $model, $systemPrompt, $temperature, $maxTokens);
        } elseif ($provider === 'gemini') {
            return $this->chatGemini($message, $model, $systemPrompt, $temperature, $maxTokens);
        } elseif ($provider === 'openrouter') {
            return $this->chatOpenRouter($message, $model, $systemPrompt, $temperature, $maxTokens);
        }

        throw new \RuntimeException("Neznámý provider: {$provider}");
    }

    /**
     * Smart chat - tries Gemini first, falls back to Groq, then OpenRouter.
     */
    public function smartChat(
        string $message,
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1024
    ): array {
        $errors = [];

        // Try Gemini first
        if ($this->geminiApiKey) {
            try {
                $result = $this->chatGemini($message, null, $systemPrompt, $temperature, $maxTokens);
                $result['smart_routing'] = [
                    'primary_provider' => 'gemini',
                    'used_provider' => 'gemini',
                    'fallback_used' => false,
                    'fallback_reason' => null,
                ];
                return $result;
            } catch (\Exception $e) {
                $errors['gemini'] = $e->getMessage();
            }
        } else {
            $errors['gemini'] = 'API key not configured';
        }

        // Fallback to Groq
        if ($this->groqApiKey) {
            try {
                $result = $this->chatGroq($message, null, $systemPrompt, $temperature, $maxTokens);
                $result['smart_routing'] = [
                    'primary_provider' => 'gemini',
                    'used_provider' => 'groq',
                    'fallback_used' => true,
                    'fallback_reason' => 'Gemini: ' . $errors['gemini'],
                ];
                return $result;
            } catch (\Exception $e) {
                $errors['groq'] = $e->getMessage();
            }
        } else {
            $errors['groq'] = 'API key not configured';
        }

        // Final fallback to OpenRouter
        if ($this->openrouterApiKey) {
            try {
                $result = $this->chatOpenRouter($message, null, $systemPrompt, $temperature, $maxTokens);
                $result['smart_routing'] = [
                    'primary_provider' => 'gemini',
                    'used_provider' => 'openrouter',
                    'fallback_used' => true,
                    'fallback_reason' => 'Gemini: ' . $errors['gemini'] . ', Groq: ' . $errors['groq'],
                ];
                return $result;
            } catch (\Exception $e) {
                $errors['openrouter'] = $e->getMessage();
            }
        } else {
            $errors['openrouter'] = 'API key not configured';
        }

        throw new \RuntimeException('Všichni provideři selhali. ' . json_encode($errors));
    }

    /**
     * Chat using Groq API.
     */
    private function chatGroq(
        string $message,
        ?string $model = null,
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1024
    ): array {
        if (!$this->groqApiKey) {
            throw new \RuntimeException('Groq API klíč není nakonfigurován');
        }

        $model = $model ?? 'llama-3.3-70b-versatile';

        if (!isset(self::GROQ_MODELS[$model])) {
            throw new \RuntimeException("Neplatný Groq model: {$model}");
        }

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $startTime = microtime(true);

        $response = $this->httpClient->postJson(
            self::GROQ_API_URL,
            [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ],
            [
                'headers' => [
                    'Authorization: Bearer ' . $this->groqApiKey,
                ],
            ]
        );

        $duration = round((microtime(true) - $startTime) * 1000);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Neplatná odpověď z Groq API: ' . json_encode($response));
        }

        return [
            'provider' => 'groq',
            'model' => $model,
            'model_description' => self::GROQ_MODELS[$model],
            'response' => $response['choices'][0]['message']['content'],
            'usage' => [
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ],
            'duration_ms' => $duration,
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
        ];
    }

    /**
     * Chat using Google Gemini API.
     */
    private function chatGemini(
        string $message,
        ?string $model = null,
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1024
    ): array {
        if (!$this->geminiApiKey) {
            throw new \RuntimeException('Gemini API klíč není nakonfigurován');
        }

        $model = $model ?? 'gemini-2.0-flash';

        if (!isset(self::GEMINI_MODELS[$model])) {
            throw new \RuntimeException("Neplatný Gemini model: {$model}");
        }

        $url = self::GEMINI_API_URL . "/{$model}:generateContent?key=" . $this->geminiApiKey;

        $contents = [];
        if ($systemPrompt) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "System: {$systemPrompt}"]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Understood. I will follow these instructions.']],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $startTime = microtime(true);

        $response = $this->httpClient->postJson(
            $url,
            [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]
        );

        $duration = round((microtime(true) - $startTime) * 1000);

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $error = $response['error']['message'] ?? json_encode($response);
            throw new \RuntimeException('Neplatná odpověď z Gemini API: ' . $error);
        }

        $usageMetadata = $response['usageMetadata'] ?? [];

        return [
            'provider' => 'gemini',
            'model' => $model,
            'model_description' => self::GEMINI_MODELS[$model],
            'response' => $response['candidates'][0]['content']['parts'][0]['text'],
            'usage' => [
                'prompt_tokens' => $usageMetadata['promptTokenCount'] ?? 0,
                'completion_tokens' => $usageMetadata['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usageMetadata['totalTokenCount'] ?? 0,
            ],
            'duration_ms' => $duration,
            'finish_reason' => $response['candidates'][0]['finishReason'] ?? null,
        ];
    }

    /**
     * Chat using OpenRouter API.
     */
    private function chatOpenRouter(
        string $message,
        ?string $model = null,
        ?string $systemPrompt = null,
        float $temperature = 0.7,
        int $maxTokens = 1024
    ): array {
        if (!$this->openrouterApiKey) {
            throw new \RuntimeException('OpenRouter API klíč není nakonfigurován');
        }

        $model = $model ?? self::OPENROUTER_DEFAULT_MODEL;

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $startTime = microtime(true);

        $response = $this->httpClient->postJson(
            self::OPENROUTER_API_URL,
            [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ],
            [
                'headers' => [
                    'Authorization: Bearer ' . $this->openrouterApiKey,
                    'HTTP-Referer: https://api.lury.cz',
                    'X-Title: pLBOT API',
                ],
            ]
        );

        $duration = round((microtime(true) - $startTime) * 1000);

        if (!isset($response['choices'][0]['message']['content'])) {
            $error = $response['error']['message'] ?? json_encode($response);
            throw new \RuntimeException('Neplatná odpověď z OpenRouter API: ' . $error);
        }

        $freeModels = $this->getOpenRouterFreeModels();
        $modelDescription = $freeModels[$model] ?? $model;

        return [
            'provider' => 'openrouter',
            'model' => $model,
            'model_description' => $modelDescription,
            'response' => $response['choices'][0]['message']['content'],
            'usage' => [
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ],
            'duration_ms' => $duration,
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
        ];
    }

    /**
     * Summarize text using AI.
     */
    public function summarize(
        string $text,
        string $provider = 'groq',
        ?string $model = null,
        string $language = 'cs'
    ): array {
        $langName = $language === 'cs' ? 'česky' : ($language === 'en' ? 'anglicky' : $language);
        $systemPrompt = "Jsi expert na sumarizaci textu. Vytvoř stručné a výstižné shrnutí. Odpovídej {$langName}.";
        $message = "Shrň následující text:\n\n{$text}";

        $result = $this->chat($message, $provider, $model, $systemPrompt, 0.3, 512);
        $result['task'] = 'summarize';
        $result['language'] = $language;

        return $result;
    }

    /**
     * Translate text using AI.
     */
    public function translate(
        string $text,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        string $provider = 'groq',
        ?string $model = null
    ): array {
        $langNames = [
            'cs' => 'češtiny', 'en' => 'angličtiny', 'de' => 'němčiny',
            'fr' => 'francouzštiny', 'es' => 'španělštiny', 'pl' => 'polštiny',
            'sk' => 'slovenštiny', 'it' => 'italštiny', 'ru' => 'ruštiny',
        ];

        $targetName = $langNames[$targetLanguage] ?? $targetLanguage;
        $sourceName = $sourceLanguage ? ($langNames[$sourceLanguage] ?? $sourceLanguage) : null;
        $sourcePart = $sourceName ? " z {$sourceName}" : '';

        $systemPrompt = "Jsi profesionální překladatel. Překládej přesně a zachovej význam i tón originálu. Vrať pouze překlad, nic jiného.";
        $message = "Přelož následující text{$sourcePart} do {$targetName}:\n\n{$text}";

        $result = $this->chat($message, $provider, $model, $systemPrompt, 0.2, 2048);
        $result['task'] = 'translate';
        $result['source_language'] = $sourceLanguage;
        $result['target_language'] = $targetLanguage;

        return $result;
    }

    /**
     * Analyze sentiment of text.
     */
    public function analyzeSentiment(
        string $text,
        string $provider = 'groq',
        ?string $model = null
    ): array {
        $systemPrompt = "Analyzuj sentiment textu. Odpověz pouze ve formátu JSON: {\"sentiment\": \"positive|negative|neutral\", \"confidence\": 0.0-1.0, \"emotions\": [\"emotion1\", \"emotion2\"], \"summary\": \"krátké vysvětlení\"}";
        $message = "Analyzuj sentiment tohoto textu:\n\n{$text}";

        $result = $this->chat($message, $provider, $model, $systemPrompt, 0.1, 256);

        // Parse JSON response
        $jsonMatch = [];
        if (preg_match('/\{[^{}]*\}/s', $result['response'], $jsonMatch)) {
            $parsed = json_decode($jsonMatch[0], true);
            if ($parsed) {
                $result['sentiment'] = $parsed;
                $result['response'] = $parsed['summary'] ?? $result['response'];
            }
        }

        $result['task'] = 'sentiment';

        return $result;
    }

    /**
     * Generate code using AI.
     */
    public function generateCode(
        string $description,
        string $language = 'php',
        string $provider = 'groq',
        ?string $model = null
    ): array {
        $systemPrompt = "Jsi expert programátor. Generuj čistý, dobře strukturovaný a komentovaný kód. Vrať pouze kód bez dalšího vysvětlování, pokud není nutné.";
        $message = "Napiš {$language} kód pro: {$description}";

        $result = $this->chat($message, $provider, $model, $systemPrompt, 0.3, 2048);
        $result['task'] = 'code_generation';
        $result['programming_language'] = $language;

        return $result;
    }

    /**
     * Answer questions about given context.
     */
    public function answerQuestion(
        string $question,
        string $context,
        string $provider = 'groq',
        ?string $model = null
    ): array {
        $systemPrompt = "Odpovídej na otázky pouze na základě poskytnutého kontextu. Pokud odpověď není v kontextu, řekni to.";
        $message = "Kontext:\n{$context}\n\nOtázka: {$question}";

        $result = $this->chat($message, $provider, $model, $systemPrompt, 0.3, 1024);
        $result['task'] = 'question_answering';

        return $result;
    }
}
