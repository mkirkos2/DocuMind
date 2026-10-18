<?php

namespace Tests\Unit\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use App\Services\DocumentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected DocumentSearchService $documentSearchService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create the service
        $this->documentSearchService = new DocumentSearchService();
    }

    public function test_search_user_documents_returns_only_users_own_documents()
    {
        // Arrange
        $this->actingAs($this->user);

        // Create user's document with chunks
        $userDocument = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
            'title' => 'User Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $userDocument->id,
            'chunk_text' => 'This is a test document about Laravel framework',
            'chunk_order' => 0,
        ]);

        // Create other user's document with chunks
        $otherDocument = Document::factory()->create([
            'user_id' => $this->otherUser->id,
            'processing_status' => 'completed',
            'title' => 'Other User Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $otherDocument->id,
            'chunk_text' => 'This is another test document about Python programming',
            'chunk_order' => 0,
        ]);

        // Act
        $results = $this->documentSearchService->searchUserDocuments('test', 10);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('User Document', $results[0]['document_title']);
        $this->assertEquals($userDocument->id, $results[0]['document_id']);
    }

    public function test_search_user_documents_excludes_non_completed_documents()
    {
        // Arrange
        $this->actingAs($this->user);

        // Create a pending document
        $pendingDocument = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'pending',
            'title' => 'Pending Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $pendingDocument->id,
            'chunk_text' => 'This document is still being processed',
            'chunk_order' => 0,
        ]);

        // Create a completed document
        $completedDocument = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
            'title' => 'Completed Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $completedDocument->id,
            'chunk_text' => 'This document has been processed successfully',
            'chunk_order' => 0,
        ]);

        // Act
        $results = $this->documentSearchService->searchUserDocuments('document', 10);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Completed Document', $results[0]['document_title']);
    }

    public function test_search_user_documents_returns_empty_for_no_matches()
    {
        // Arrange
        $this->actingAs($this->user);

        // Create a document
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'This document contains unrelated content',
            'chunk_order' => 0,
        ]);

        // Act
        $results = $this->documentSearchService->searchUserDocuments('quantum physics', 10);

        // Assert
        $this->assertEmpty($results);
    }

    public function test_search_user_documents_respects_limit()
    {
        // Arrange
        $this->actingAs($this->user);

        // Create a document with multiple chunks that match
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
            'title' => 'Multi-chunk Document',
        ]);

        // Create 5 chunks with matching content
        for ($i = 0; $i < 5; $i++) {
            DocumentChunk::factory()->create([
                'document_id' => $document->id,
                'chunk_text' => "This is chunk {$i} about important topics",
                'chunk_order' => $i,
            ]);
        }

        // Act
        $results = $this->documentSearchService->searchUserDocuments('important', 3);

        // Assert
        $this->assertCount(3, $results);
    }

    public function test_search_user_documents_returns_only_requested_page()
    {
        $this->actingAs($this->user);

        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
            'title' => 'Paged Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'Content from page one',
            'chunk_order' => 0,
            'page_number' => 1,
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'Content from page two',
            'chunk_order' => 1,
            'page_number' => 2,
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'Content from page three',
            'chunk_order' => 2,
            'page_number' => 3,
        ]);

        $results = $this->documentSearchService->searchUserDocuments(
            'what does page 2 say?',
            10
        );

        $this->assertCount(1, $results);
        $this->assertSame(2, (int) $results[0]['page_number']);
        $this->assertSame('Content from page two', $results[0]['chunk_text']);
    }

    public function test_search_user_documents_returns_empty_for_nonexistent_page()
    {
        $this->actingAs($this->user);

        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'Content from page one',
            'chunk_order' => 0,
            'page_number' => 1,
        ]);

        $results = $this->documentSearchService->searchUserDocuments(
            'what does page 9 say?',
            10
        );

        $this->assertEmpty($results);
    }}