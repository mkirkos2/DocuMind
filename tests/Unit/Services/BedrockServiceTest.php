<?php

namespace Tests\Unit\Services;

use App\Services\BedrockService;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Result;
use Illuminate\Support\Facades\Config;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BedrockServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_throws_exception_when_configuration_is_incomplete()
    {
        Config::set('services.bedrock', [
            'region' => '',
            'key' => '',
            'secret' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bedrock service configuration is incomplete');

        new BedrockService();
    }

    public function test_generate_text_returns_response_from_bedrock()
    {
        // Mock the configuration
        Config::set('services.bedrock', [
            'region' => 'us-east-1',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'model_id' => 'test-model-id',
        ]);

        // Create a mock BedrockRuntimeClient
        $mockClient = Mockery::mock(BedrockRuntimeClient::class);
        $mockResult = new Result([
            'output' => [
                'message' => [
                    'content' => [
                        ['text' => 'Generated response from Bedrock'],
                    ],
                ],
            ],
        ]);

        $mockClient->shouldReceive('converse')
            ->once()
            ->andReturn($mockResult);

        // Use reflection to set the client property
        $service = new BedrockService();
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($service, $mockClient);

        $response = $service->generateText('System instruction', 'User prompt');
        $this->assertEquals('Generated response from Bedrock', $response);
    }

    public function test_get_model_id_returns_configured_model()
    {
        Config::set('services.bedrock', [
            'region' => 'us-east-1',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'model_id' => 'test-model-id',
        ]);

        $service = new BedrockService();
        $this->assertEquals('test-model-id', $service->getModelId());
    }

    public function test_default_model_id_is_used_when_not_configured()
    {
        Config::set('services.bedrock', [
            'region' => 'us-east-1',
            'key' => 'test-key',
            'secret' => 'test-secret',
        ]);

        $service = new BedrockService();
        $this->assertEquals('anthropic.claude-3-sonnet-20240229-v1:0', $service->getModelId());
    }
}