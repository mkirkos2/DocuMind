<?php

namespace Tests\Unit\Services;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    public function test_constructor_throws_exception_when_api_key_is_missing()
    {
        // Completely reset the config
        config(['services.gemini' => ['api_key' => null, 'model' => null]]);
        // Set the API key to empty
        putenv('GEMINI_API_KEY=');
        
        // Clear any cached env values
        $_ENV['GEMINI_API_KEY'] = '';
        $_SERVER['GEMINI_API_KEY'] = '';

        try {
            new GeminiService();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Gemini service configuration is incomplete. Please set GEMINI_API_KEY in your .env file.', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('Wrong exception type thrown: ' . get_class($e));
        }
    }

    public function test_generate_text_returns_response_from_gemini()
    {
        // Set a fake API key for testing
        $_ENV['GEMINI_API_KEY'] = 'fake-api-key';
        $_ENV['GEMINI_MODEL'] = 'gemini-1.5-flash';
        
        // Mock the HTTP response
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Generated response from Gemini']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GeminiService();
        $response = $service->generateText('System instruction', 'User prompt');
        
        $this->assertEquals('Generated response from Gemini', $response);
    }

    public function test_generate_text_handles_authentication_error()
    {
        // Set a fake API key for testing
        $_ENV['GEMINI_API_KEY'] = 'fake-api-key';
        $_ENV['GEMINI_MODEL'] = 'gemini-1.5-flash';
        
        // Mock a 401 response with exact URL
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=fake-api-key' => Http::response([
                'error' => [
                    'message' => 'API key not valid.'
                ]
            ], 401)
        ]);

        $service = new GeminiService();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Authentication failed. Please check your GEMINI_API_KEY.');

        $service->generateText('System instruction', 'User prompt');
    }

    public function test_generate_text_handles_rate_limiting()
    {
        // Set a fake API key for testing
        $_ENV['GEMINI_API_KEY'] = 'fake-api-key';
        $_ENV['GEMINI_MODEL'] = 'gemini-1.5-flash';
        
        // Mock a 429 response
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent*' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded.'
                ]
            ], 429)
        ]);

        $service = new GeminiService();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request was throttled. Please try again later.');

        $service->generateText('System instruction', 'User prompt');
    }

    public function test_get_model_id_returns_configured_model()
    {
        // Set a fake API key and model for testing
        $_ENV['GEMINI_API_KEY'] = 'fake-api-key';
        $_ENV['GEMINI_MODEL'] = 'test-model-id';

        $service = new GeminiService();
        $this->assertEquals('test-model-id', $service->getModelId());
    }

    public function test_default_model_id_is_used_when_not_configured()
    {
        // Mock config to return a fake API key and empty model
        config(['services.gemini.api_key' => 'fake-api-key']);
        config(['services.gemini.model' => null]);
        // Set the API key to empty
        putenv('GEMINI_MODEL=');

        $service = new GeminiService();
        $this->assertEquals('gemini-3-flash-preview', $service->getModelId());
    }

    public function test_generate_text_handles_model_not_found_error()
    {
        // Set a fake API key and invalid model for testing
        $_ENV['GEMINI_API_KEY'] = 'fake-api-key';
        $_ENV['GEMINI_MODEL'] = 'invalid-model';
        
        // Mock a 404 response
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/invalid-model:generateContent*' => Http::response([
                'error' => [
                    'message' => 'Model not found'
                ]
            ], 404)
        ]);

        $service = new GeminiService();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model not found. Please check your GEMINI_MODEL configuration.');

        $service->generateText('System instruction', 'User prompt');
    }
}