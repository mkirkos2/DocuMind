<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_authenticated_user_can_search_their_own_documents()
    {
        // Create documents for the authenticated user
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'This is a test document with important content',
            'chunk_order' => 0,
            'character_count' => 45
        ]);

        // Perform search
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'test']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'test')
            ->has('results', 1)
        );
    }

    public function test_search_returns_empty_results_for_no_matches()
    {
        // Create documents for the authenticated user
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'This is a test document with important content',
            'chunk_order' => 0,
            'character_count' => 45
        ]);

        // Perform search with no matches
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'nonexistent']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'nonexistent')
            ->has('results', 0)
        );
    }

    public function test_user_cannot_see_other_users_documents_in_search_results()
    {
        // Create document for the other user
        $otherDocument = Document::factory()->create([
            'user_id' => $this->otherUser->id,
            'title' => 'Other User Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $otherDocument->id,
            'chunk_text' => 'This document belongs to another user and contains secret information',
            'chunk_order' => 0,
            'character_count' => 70
        ]);

        // Try to search for content that exists in other user's document
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'secret']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'secret')
            ->has('results', 0) // Should not see other user's documents
        );
    }

    public function test_only_completed_documents_are_searched()
    {
        // Create completed document
        $completedDocument = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Completed Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $completedDocument->id,
            'chunk_text' => 'This is a completed document',
            'chunk_order' => 0,
            'character_count' => 28
        ]);

        // Create pending document
        $pendingDocument = Document::factory()->pending()->create([
            'user_id' => $this->user->id,
            'title' => 'Pending Document',
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $pendingDocument->id,
            'chunk_text' => 'This is a pending document',
            'chunk_order' => 0,
            'character_count' => 27
        ]);

        // Search for content that exists in both documents
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'document']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'document')
            ->has('results', 1) // Should only see the completed document
        );
    }

    public function test_search_results_are_limited_to_10_chunks()
    {
        // Create a document with many chunks
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Large Document',
            'processing_status' => 'completed'
        ]);

        // Create 15 chunks, all matching the search term
        for ($i = 0; $i < 15; $i++) {
            DocumentChunk::factory()->create([
                'document_id' => $document->id,
                'chunk_text' => "Chunk {$i} with search term match",
                'chunk_order' => $i,
                'character_count' => 30
            ]);
        }

        // Perform search
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'search']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'search')
            ->has('results', 10) // Should be limited to 10 results
        );
    }

    public function test_multiple_search_terms_are_handled_correctly()
    {
        // Create documents with varying relevance
        $document1 = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document1->id,
            'chunk_text' => 'This document contains important information and key points',
            'chunk_order' => 0,
            'character_count' => 55
        ]);

        $document2 = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Basic Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document2->id,
            'chunk_text' => 'This document has basic content',
            'chunk_order' => 0,
            'character_count' => 32
        ]);

        // Search with multiple terms
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'important information']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', 'important information')
            ->has('results', 1) // Should find the document with both terms
        );
    }

    public function test_search_respects_query_length_limits()
    {
        // Create a document
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_text' => 'This is a test document',
            'chunk_order' => 0,
            'character_count' => 24
        ]);

        // Test with empty query
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => '']));

        $response->assertStatus(200);

        // Test with very long query (over 255 characters)
        $longQuery = str_repeat('a', 256);
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => $longQuery]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Search')
            ->where('query', $longQuery)
            ->has('results', 0) // Should return empty results
        );
    }

    public function test_relevance_scoring_works_correctly()
    {
        // Create documents with varying relevance
        $document1 = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'High Relevance Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document1->id,
            'chunk_text' => 'Search term appears multiple times: search term, search term, search term',
            'chunk_order' => 0,
            'character_count' => 75
        ]);

        $document2 = Document::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Low Relevance Document',
            'processing_status' => 'completed'
        ]);

        DocumentChunk::factory()->create([
            'document_id' => $document2->id,
            'chunk_text' => 'Search term appears once',
            'chunk_order' => 0,
            'character_count' => 25
        ]);

        // Perform search
        $response = $this->actingAs($this->user)
            ->get(route('documents.search', ['q' => 'search term']));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Documents/Search')
                ->where('query', 'search term')
                ->has('results', 2);

            // Verify results are ordered by relevance score (higher first)
            $results = $page->toArray()['props']['results'];
            $this->assertGreaterThanOrEqual($results[1]['relevance_score'], $results[0]['relevance_score']);
        });
    }
}