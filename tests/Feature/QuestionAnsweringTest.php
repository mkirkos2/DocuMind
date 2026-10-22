<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GeminiService;
use App\Services\DocumentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QuestionAnsweringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_question_answering_endpoint_requires_authentication()
    {
        // Mock the services to bypass Gemini configuration issues
        $documentSearchService = Mockery::mock(DocumentSearchService::class);
        $geminiService = Mockery::mock(GeminiService::class);

        // Bind mocks to container
        $this->app->instance(DocumentSearchService::class, $documentSearchService);
        $this->app->instance(GeminiService::class, $geminiService);

        // Act
        $response = $this->postJson(route('question.answer'), [
            'question' => 'What is Laravel?',
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_question_answering_endpoint_validates_question()
    {
        // Arrange
        $this->actingAs($this->user);

        // Mock the services to bypass Gemini configuration issues
        $documentSearchService = Mockery::mock(DocumentSearchService::class);
        $geminiService = Mockery::mock(GeminiService::class);

        // Bind mocks to container
        $this->app->instance(DocumentSearchService::class, $documentSearchService);
        $this->app->instance(GeminiService::class, $geminiService);

        // Act
        $response = $this->postJson(route('question.answer'), [
            'question' => '', // Empty question
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'message',
        ]);
    }

    public function test_question_answering_endpoint_handles_long_questions()
    {
        // Arrange
        $this->actingAs($this->user);

        // Mock the services to bypass Gemini configuration issues
        $documentSearchService = Mockery::mock(DocumentSearchService::class);
        $geminiService = Mockery::mock(GeminiService::class);

        // Bind mocks to container
        $this->app->instance(DocumentSearchService::class, $documentSearchService);
        $this->app->instance(GeminiService::class, $geminiService);

        $longQuestion = str_repeat('a', 2001); // Exceeds max length of 2000

        // Act
        $response = $this->postJson(route('question.answer'), [
            'question' => $longQuestion,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'message',
        ]);
    }

    public function test_question_answering_endpoint_returns_success_for_valid_question()
    {
        // Arrange
        $this->actingAs($this->user);

        // Mock the services
        $documentSearchService = Mockery::mock(DocumentSearchService::class);
        $geminiService = Mockery::mock(GeminiService::class);

        // Create actual document and chunk for foreign key constraints
        $document = \App\Models\Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel Guide',
        ]);

        $documentChunk = \App\Models\DocumentChunk::factory()->create([
            'document_id' => $document->id,
        ]);

        // Set up expectations
        $documentSearchService->shouldReceive('searchUserDocuments')
            ->with('What is Laravel?', 5)
            ->andReturn([
                [
                    'document_id' => $document->id,
                    'document_title' => 'Laravel Guide',
                    'chunk_id' => $documentChunk->id,
                    'chunk_text' => 'Laravel is a PHP web application framework.',
                    'chunk_order' => 0,
                    'page_number' => 1,
                    'character_count' => 45,
                    'relevance_score' => 150,
                ]
            ]);

        $geminiService->shouldReceive('generateText')
            ->andReturn('Laravel is a PHP web application framework.');

        // Bind mocks to container
        $this->app->instance(DocumentSearchService::class, $documentSearchService);
        $this->app->instance(GeminiService::class, $geminiService);

        // Act
        $response = $this->postJson(route('question.answer'), [
            'question' => 'What is Laravel?',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'answer',
                    'sources',
                ],
            ]);

        // Dump the response for debugging
        // $response->dump();
    }
}