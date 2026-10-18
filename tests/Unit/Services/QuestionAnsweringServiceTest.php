<?php

namespace Tests\Unit\Services;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\User;
use App\Services\OllamaService;
use App\Services\DocumentSearchService;
use App\Services\QuestionAnsweringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QuestionAnsweringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DocumentSearchService $documentSearchService;
    protected OllamaService $bedrockServiceMock;
    protected QuestionAnsweringService $questionAnsweringService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create mocks
        $this->documentSearchService = Mockery::mock(DocumentSearchService::class);
        $this->bedrockServiceMock = Mockery::mock(OllamaService::class);

        // Create the service
        $this->questionAnsweringService = new QuestionAnsweringService(
            $this->bedrockServiceMock,
            $this->documentSearchService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_answer_question_with_relevant_chunks()
    {
        // Arrange
        $question = "What is Laravel?";

        // Create actual document and chunk for foreign key constraints
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel Guide',
        ]);

        $documentChunk = \App\Models\DocumentChunk::factory()->create([
            'document_id' => $document->id,
        ]);

        $chunks = [
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
        ];

        $this->documentSearchService->shouldReceive('searchUserDocuments')
            ->with($question, 5)
            ->andReturn($chunks);

        $this->bedrockServiceMock->shouldReceive('generateText')
            ->andReturn('Laravel is a PHP web application framework.');

        // Act
        $result = $this->questionAnsweringService->answerQuestion($question);

        // Assert
        $this->assertArrayHasKey('answer', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertEquals('Laravel is a PHP web application framework.', $result['answer']);
        $this->assertCount(1, $result['sources']);

        // Check that conversation and messages were created
        $this->assertDatabaseHas('conversations', [
            'user_id' => $this->user->id,
        ]);

        $conversation = Conversation::first();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $question,
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Laravel is a PHP web application framework.',
        ]);
    }

    public function test_answer_question_with_no_relevant_chunks()
    {
        // Arrange
        $question = "What is quantum physics?";

        $this->documentSearchService->shouldReceive('searchUserDocuments')
            ->with($question, 5)
            ->andReturn([]);

        // Act
        $result = $this->questionAnsweringService->answerQuestion($question);

        // Assert
        $this->assertArrayHasKey('answer', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertEquals(
            "I couldn't find any relevant information in your documents to answer this question.",
            $result['answer']
        );
        $this->assertEmpty($result['sources']);
    }

    public function test_answer_question_with_bedrock_service_failure()
    {
        // Arrange
        $question = "What is Laravel?";

        // Create actual document and chunk for foreign key constraints
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel Guide',
        ]);

        $documentChunk = \App\Models\DocumentChunk::factory()->create([
            'document_id' => $document->id,
        ]);

        $chunks = [
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
        ];

        $this->documentSearchService->shouldReceive('searchUserDocuments')
            ->with($question, 5)
            ->andReturn($chunks);

        $this->bedrockServiceMock->shouldReceive('generateText')
            ->andThrow(new \RuntimeException('Bedrock service error'));

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate answer: Bedrock service error');

        $this->questionAnsweringService->answerQuestion($question);
    }
}