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
use App\Services\AiService;

#[Path('/ai')]
#[Tag('AI Services')]
final class AiController extends BaseController
{
    public function __construct(
        private readonly AiService $aiService
    ) {
    }

    #[Path('/providers')]
    #[Method('GET')]
    #[ApiResponse(code: '200', description: 'Seznam dostupných AI providerů')]
    public function getProviders(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->aiService->getProviders();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    // ==================== SMART ENDPOINT (AUTO-FALLBACK) ====================

    #[Path('/chat')]
    #[Method('GET')]
    #[RequestParameter(name: 'message', type: 'string', in: 'query', required: true, description: 'Zpráva pro AI')]
    #[RequestParameter(name: 'system', type: 'string', in: 'query', required: false, description: 'Systémový prompt (volitelné)')]
    #[RequestParameter(name: 'temperature', type: 'string', in: 'query', required: false, description: 'Teplota 0.0-2.0 (default: 0.7)')]
    #[RequestParameter(name: 'max_tokens', type: 'string', in: 'query', required: false, description: 'Max tokenů (default: 1024)')]
    #[ApiResponse(code: '200', description: 'AI odpověď (automatický fallback Gemini -> Groq)')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    #[ApiResponse(code: '500', description: 'Chyba AI služby')]
    public function smartChat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $message = trim($request->getParameter('message') ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $systemPrompt = $request->getParameter('system');
            $temperature = (float) ($request->getParameter('temperature') ?? 0.7);
            $maxTokens = (int) ($request->getParameter('max_tokens') ?? 1024);

            if ($temperature < 0 || $temperature > 2) {
                return $this->createErrorResponse($response, 'Temperature musí být mezi 0.0 a 2.0', 400);
            }
            if ($maxTokens < 1 || $maxTokens > 8192) {
                return $this->createErrorResponse($response, 'max_tokens musí být mezi 1 a 8192', 400);
            }

            $data = $this->aiService->smartChat($message, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/chat')]
    #[Method('POST')]
    #[ApiResponse(code: '200', description: 'AI odpověď (automatický fallback Gemini -> Groq)')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    #[ApiResponse(code: '500', description: 'Chyba AI služby')]
    public function smartChatPost(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $body = $request->getJsonBody();

            $message = trim($body['message'] ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $systemPrompt = $body['system'] ?? null;
            $temperature = (float) ($body['temperature'] ?? 0.7);
            $maxTokens = (int) ($body['max_tokens'] ?? 1024);

            $data = $this->aiService->smartChat($message, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }

    // ==================== GROQ ENDPOINTS ====================

    #[Path('/groq/chat')]
    #[Method('GET')]
    #[RequestParameter(name: 'message', type: 'string', in: 'query', required: true, description: 'Zpráva pro AI')]
    #[RequestParameter(name: 'model', type: 'string', in: 'query', required: false, description: 'Model: llama-3.3-70b-versatile, llama-3.1-8b-instant, mixtral-8x7b-32768, gemma2-9b-it')]
    #[RequestParameter(name: 'system', type: 'string', in: 'query', required: false, description: 'Systémový prompt')]
    #[RequestParameter(name: 'temperature', type: 'string', in: 'query', required: false, description: 'Teplota 0.0-2.0')]
    #[RequestParameter(name: 'max_tokens', type: 'string', in: 'query', required: false, description: 'Max tokenů')]
    #[ApiResponse(code: '200', description: 'Groq AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function groqChat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $message = trim($request->getParameter('message') ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $request->getParameter('model');
            $systemPrompt = $request->getParameter('system');
            $temperature = (float) ($request->getParameter('temperature') ?? 0.7);
            $maxTokens = (int) ($request->getParameter('max_tokens') ?? 1024);

            $data = $this->aiService->chat($message, 'groq', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba Groq API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/groq/chat')]
    #[Method('POST')]
    #[ApiResponse(code: '200', description: 'Groq AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function groqChatPost(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $body = $request->getJsonBody();

            $message = trim($body['message'] ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $body['model'] ?? null;
            $systemPrompt = $body['system'] ?? null;
            $temperature = (float) ($body['temperature'] ?? 0.7);
            $maxTokens = (int) ($body['max_tokens'] ?? 1024);

            $data = $this->aiService->chat($message, 'groq', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba Groq API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/groq/models')]
    #[Method('GET')]
    #[ApiResponse(code: '200', description: 'Seznam Groq modelů')]
    public function groqModels(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        $providers = $this->aiService->getProviders();
        return $this->createSuccessResponse($response, [
            'provider' => 'groq',
            'models' => $providers['groq']['models'],
            'default_model' => $providers['groq']['default_model'],
            'available' => $providers['groq']['available'],
        ]);
    }

    // ==================== GEMINI ENDPOINTS ====================

    #[Path('/gemini/chat')]
    #[Method('GET')]
    #[RequestParameter(name: 'message', type: 'string', in: 'query', required: true, description: 'Zpráva pro AI')]
    #[RequestParameter(name: 'model', type: 'string', in: 'query', required: false, description: 'Model: gemini-2.0-flash, gemini-2.5-flash, gemini-2.5-pro')]
    #[RequestParameter(name: 'system', type: 'string', in: 'query', required: false, description: 'Systémový prompt')]
    #[RequestParameter(name: 'temperature', type: 'string', in: 'query', required: false, description: 'Teplota 0.0-2.0')]
    #[RequestParameter(name: 'max_tokens', type: 'string', in: 'query', required: false, description: 'Max tokenů')]
    #[ApiResponse(code: '200', description: 'Gemini AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function geminiChat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $message = trim($request->getParameter('message') ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $request->getParameter('model');
            $systemPrompt = $request->getParameter('system');
            $temperature = (float) ($request->getParameter('temperature') ?? 0.7);
            $maxTokens = (int) ($request->getParameter('max_tokens') ?? 1024);

            $data = $this->aiService->chat($message, 'gemini', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba Gemini API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/gemini/chat')]
    #[Method('POST')]
    #[ApiResponse(code: '200', description: 'Gemini AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function geminiChatPost(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $body = $request->getJsonBody();

            $message = trim($body['message'] ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $body['model'] ?? null;
            $systemPrompt = $body['system'] ?? null;
            $temperature = (float) ($body['temperature'] ?? 0.7);
            $maxTokens = (int) ($body['max_tokens'] ?? 1024);

            $data = $this->aiService->chat($message, 'gemini', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba Gemini API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/gemini/models')]
    #[Method('GET')]
    #[ApiResponse(code: '200', description: 'Seznam Gemini modelů')]
    public function geminiModels(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        $providers = $this->aiService->getProviders();
        return $this->createSuccessResponse($response, [
            'provider' => 'gemini',
            'models' => $providers['gemini']['models'],
            'default_model' => $providers['gemini']['default_model'],
            'available' => $providers['gemini']['available'],
        ]);
    }

    // ==================== OPENROUTER ENDPOINTS ====================

    #[Path('/openrouter/chat')]
    #[Method('GET')]
    #[RequestParameter(name: 'message', type: 'string', in: 'query', required: true, description: 'Zpráva pro AI')]
    #[RequestParameter(name: 'model', type: 'string', in: 'query', required: false, description: 'Model z OpenRouter (viz /ai/openrouter/models)')]
    #[RequestParameter(name: 'system', type: 'string', in: 'query', required: false, description: 'Systémový prompt')]
    #[RequestParameter(name: 'temperature', type: 'string', in: 'query', required: false, description: 'Teplota 0.0-2.0')]
    #[RequestParameter(name: 'max_tokens', type: 'string', in: 'query', required: false, description: 'Max tokenů')]
    #[ApiResponse(code: '200', description: 'OpenRouter AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function openrouterChat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $message = trim($request->getParameter('message') ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $request->getParameter('model');
            $systemPrompt = $request->getParameter('system');
            $temperature = (float) ($request->getParameter('temperature') ?? 0.7);
            $maxTokens = (int) ($request->getParameter('max_tokens') ?? 1024);

            $data = $this->aiService->chat($message, 'openrouter', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba OpenRouter API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/openrouter/chat')]
    #[Method('POST')]
    #[ApiResponse(code: '200', description: 'OpenRouter AI odpověď')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function openrouterChatPost(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $body = $request->getJsonBody();

            $message = trim($body['message'] ?? '');
            if (empty($message)) {
                return $this->createErrorResponse($response, 'Parametr message je povinný', 400);
            }

            $model = $body['model'] ?? null;
            $systemPrompt = $body['system'] ?? null;
            $temperature = (float) ($body['temperature'] ?? 0.7);
            $maxTokens = (int) ($body['max_tokens'] ?? 1024);

            $data = $this->aiService->chat($message, 'openrouter', $model, $systemPrompt, $temperature, $maxTokens);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba OpenRouter API: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/openrouter/models')]
    #[Method('GET')]
    #[ApiResponse(code: '200', description: 'Seznam OpenRouter modelů')]
    public function openrouterModels(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        $providers = $this->aiService->getProviders();
        return $this->createSuccessResponse($response, [
            'provider' => 'openrouter',
            'models' => $providers['openrouter']['models'],
            'default_model' => $providers['openrouter']['default_model'],
            'available' => $providers['openrouter']['available'],
        ]);
    }

    // ==================== UTILITY ENDPOINTS ====================

    #[Path('/summarize')]
    #[Method('GET')]
    #[RequestParameter(name: 'text', type: 'string', in: 'query', required: true, description: 'Text k sumarizaci')]
    #[RequestParameter(name: 'provider', type: 'string', in: 'query', required: false, description: 'Provider: groq, gemini, openrouter')]
    #[RequestParameter(name: 'language', type: 'string', in: 'query', required: false, description: 'Jazyk: cs, en (default: cs)')]
    #[ApiResponse(code: '200', description: 'Shrnutí textu')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function summarize(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $text = trim($request->getParameter('text') ?? '');
            if (empty($text)) {
                return $this->createErrorResponse($response, 'Parametr text je povinný', 400);
            }

            $provider = $request->getParameter('provider') ?? 'groq';
            $language = $request->getParameter('language') ?? 'cs';

            $data = $this->aiService->summarize($text, $provider, null, $language);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/translate')]
    #[Method('GET')]
    #[RequestParameter(name: 'text', type: 'string', in: 'query', required: true, description: 'Text k překladu')]
    #[RequestParameter(name: 'to', type: 'string', in: 'query', required: true, description: 'Cílový jazyk: cs, en, de, fr, es, pl, sk, it, ru')]
    #[RequestParameter(name: 'from', type: 'string', in: 'query', required: false, description: 'Zdrojový jazyk (auto-detect)')]
    #[RequestParameter(name: 'provider', type: 'string', in: 'query', required: false, description: 'Provider: groq, gemini, openrouter')]
    #[ApiResponse(code: '200', description: 'Přeložený text')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function translate(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $text = trim($request->getParameter('text') ?? '');
            $targetLanguage = trim($request->getParameter('to') ?? '');

            if (empty($text)) {
                return $this->createErrorResponse($response, 'Parametr text je povinný', 400);
            }
            if (empty($targetLanguage)) {
                return $this->createErrorResponse($response, 'Parametr to je povinný', 400);
            }

            $sourceLanguage = $request->getParameter('from');
            $provider = $request->getParameter('provider') ?? 'groq';

            $data = $this->aiService->translate($text, $targetLanguage, $sourceLanguage, $provider);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/sentiment')]
    #[Method('GET')]
    #[RequestParameter(name: 'text', type: 'string', in: 'query', required: true, description: 'Text k analýze')]
    #[RequestParameter(name: 'provider', type: 'string', in: 'query', required: false, description: 'Provider: groq, gemini, openrouter')]
    #[ApiResponse(code: '200', description: 'Analýza sentimentu')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function sentiment(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $text = trim($request->getParameter('text') ?? '');
            if (empty($text)) {
                return $this->createErrorResponse($response, 'Parametr text je povinný', 400);
            }

            $provider = $request->getParameter('provider') ?? 'groq';

            $data = $this->aiService->analyzeSentiment($text, $provider);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/code')]
    #[Method('GET')]
    #[RequestParameter(name: 'description', type: 'string', in: 'query', required: true, description: 'Popis kódu')]
    #[RequestParameter(name: 'language', type: 'string', in: 'query', required: false, description: 'Programovací jazyk (default: php)')]
    #[RequestParameter(name: 'provider', type: 'string', in: 'query', required: false, description: 'Provider: groq, gemini, openrouter')]
    #[ApiResponse(code: '200', description: 'Vygenerovaný kód')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function generateCode(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $description = trim($request->getParameter('description') ?? '');
            if (empty($description)) {
                return $this->createErrorResponse($response, 'Parametr description je povinný', 400);
            }

            $language = $request->getParameter('language') ?? 'php';
            $provider = $request->getParameter('provider') ?? 'groq';

            $data = $this->aiService->generateCode($description, $language, $provider);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba AI služby: ' . $e->getMessage(), 500);
        }
    }
}
