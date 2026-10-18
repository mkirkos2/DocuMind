<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $config = config('services.ollama', []);

        $this->baseUrl = rtrim(
            $config['base_url'] ?? 'http://127.0.0.1:11434',
            '/'
        );

        $this->model = $config['model'] ?? 'qwen2.5:3b';
        $this->timeout = (int) ($config['timeout'] ?? 120);
    }

    public function generateText(
        string $systemInstruction,
        string $userPrompt
    ): string {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($this->baseUrl . '/api/chat', [
                    'model' => $this->model,
                    'stream' => false,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemInstruction,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'options' => [
                        'temperature' => 0.2,
                        'num_predict' => 2048,
                    ],
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Ollama returned HTTP status ' . $response->status() . '.'
                );
            }

            $answer = $response->json('message.content');

            if (!is_string($answer) || trim($answer) === '') {
                throw new RuntimeException(
                    'Ollama returned an empty response.'
                );
            }

            return trim($answer);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Could not connect to Ollama. Make sure Ollama is running.',
                0,
                $e
            );
        }
    }

    public function getModelId(): string
    {
        return $this->model;
    }
}