<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    protected string $apiKey;
    protected string $modelId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $modelEnv = env('GEMINI_MODEL');
        $this->modelId = $modelEnv ?: 'gemini-1.5-flash';
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->modelId . ':generateContent';

        // Check if we have a valid API key
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini service configuration is incomplete. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * Generate text using the Gemini API
     *
     * @param string $systemInstruction
     * @param string $userPrompt
     * @return string
     * @throws RuntimeException
     */
    public function generateText(string $systemInstruction, string $userPrompt): string
    {
        try {
            $payload = [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2048,
                    'temperature' => 0.7,
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            // Handle HTTP errors
            if ($response->failed()) {
                $statusCode = $response->status();
                $errorData = $response->json() ?? [];

                // Log the actual error for debugging (but don't expose to users)
                \Log::error('Gemini API Error', [
                    'status' => $statusCode,
                    'response' => $errorData
                ]);

                // Handle specific error cases
                if ($statusCode === 401 || ($statusCode === 400 && isset($errorData['error']['message']) && strpos($errorData['error']['message'], 'API key not valid') !== false)) {
                    throw new RuntimeException('Authentication failed. Please check your GEMINI_API_KEY.');
                }

                if ($statusCode === 429) {
                    throw new RuntimeException('Request was throttled. Please try again later.');
                }

                if ($statusCode === 404) {
                    throw new RuntimeException('Model not found. Please check your GEMINI_MODEL configuration.');
                }

                if ($statusCode >= 500) {
                    throw new RuntimeException('Gemini service is temporarily unavailable. Please try again later.');
                }

                // Generic error message for other cases
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error occurred.';
                throw new RuntimeException('Failed to generate text: ' . $errorMessage);
            }

            $responseData = $response->json();

            // Extract the generated text from the response
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            }

            // Handle case where no content was generated
            if (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] === 'SAFETY') {
                throw new RuntimeException('Response blocked due to safety concerns. Please try rephrasing your question.');
            }

            // Handle case where response is empty or malformed
            if (!isset($responseData['candidates']) || empty($responseData['candidates'])) {
                if (isset($responseData['promptFeedback'])) {
                    throw new RuntimeException('Response blocked due to safety concerns in the prompt.');
                }
                throw new RuntimeException('No candidates returned from Gemini API.');
            }

            throw new RuntimeException('No text content found in Gemini response.');
        } catch (\Exception $e) {
            // Handle general exceptions
            if ($e instanceof RuntimeException) {
                throw $e; // Re-throw our own exceptions
            }
            
            \Log::error('Gemini Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new RuntimeException('Failed to generate text: ' . $e->getMessage());
        }
    }

    /**
     * Get the configured model ID
     *
     * @return string
     */
    public function getModelId(): string
    {
        return $this->modelId;
    }
}