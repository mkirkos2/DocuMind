<?php

namespace App\Services;

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;
use RuntimeException;

class BedrockService
{
    protected BedrockRuntimeClient $client;
    protected string $modelId;

    public function __construct()
    {
        $config = config('services.bedrock');

        if (empty($config['region']) || empty($config['key']) || empty($config['secret'])) {
            throw new RuntimeException('Bedrock service configuration is incomplete. Please check your AWS credentials.');
        }

        $this->modelId = $config['model_id'] ?? 'anthropic.claude-3-sonnet-20240229-v1:0';

        $clientConfig = [
            'region' => $config['region'],
            'version' => 'latest',
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ];

        if (!empty($config['session_token'])) {
            $clientConfig['credentials']['token'] = $config['session_token'];
        }

        $this->client = new BedrockRuntimeClient($clientConfig);
    }

    /**
     * Generate text using the Bedrock Converse API
     *
     * @param string $systemInstruction
     * @param string $userPrompt
     * @return string
     * @throws RuntimeException
     */
    public function generateText(string $systemInstruction, string $userPrompt): string
    {
        try {
            $result = $this->client->converse([
                'modelId' => $this->modelId,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [['text' => $userPrompt]],
                    ]
                ],
                'system' => [['text' => $systemInstruction]],
                'inferenceConfig' => [
                    'maxTokens' => 2048,
                    'temperature' => 0.7,
                ],
            ]);

            $output = $result->get('output');
            if (isset($output['message']['content'][0]['text'])) {
                return $output['message']['content'][0]['text'];
            }

            throw new RuntimeException('No text content found in Bedrock response.');
        } catch (AwsException $e) {
            // Handle AWS-specific exceptions
            $errorMessage = $e->getMessage();

            // Don't expose sensitive information
            if (strpos($errorMessage, 'credentials') !== false) {
                throw new RuntimeException('Authentication failed. Please check your AWS credentials.');
            }

            if ($e->getAwsErrorCode() === 'ThrottlingException') {
                throw new RuntimeException('Request was throttled. Please try again later.');
            }

            if ($e->getAwsErrorCode() === 'ModelTimeoutException') {
                throw new RuntimeException('Request timed out. Please try again.');
            }

            throw new RuntimeException('Failed to generate text: ' . $errorMessage);
        } catch (\Exception $e) {
            // Handle general exceptions
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