<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class DocumentPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test pagination display with multiple documents
     */
    public function test_pagination_displays_correct_counts()
    {
        $user = User::factory()->create();
        
        // Create more documents than fit on one page (10 per page)
        Document::factory()->count(15)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
        
        // Check Inertia props instead of raw HTML
        $response->assertInertia(function (Assert $page) {
            $page->component('Documents/Index')
                 ->where('documents.total', 15)
                 ->where('documents.from', 1)
                 ->where('documents.to', 10)
                 ->where('documents.per_page', 10)
                 ->where('documents.current_page', 1)
                 ->has('documents.data', 10);
        });
    }

    /**
     * Test pagination with no documents
     */
    public function test_pagination_with_no_documents()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
        
        // Check Inertia props for empty state
        $response->assertInertia(function (Assert $page) {
            $page->component('Documents/Index')
                 ->where('documents.total', 0)
                 ->where('documents.from', null)
                 ->where('documents.to', null)
                 ->where('documents.per_page', 10)
                 ->where('documents.current_page', 1)
                 ->has('documents.data', 0);
        });
    }

    /**
     * Test pagination with exactly one page of documents
     */
    public function test_pagination_with_one_page_of_documents()
    {
        $user = User::factory()->create();
        
        // Create fewer documents than fit on one page
        Document::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
        
        // Check Inertia props for single page
        $response->assertInertia(function (Assert $page) {
            $page->component('Documents/Index')
                 ->where('documents.total', 5)
                 ->where('documents.from', 1)
                 ->where('documents.to', 5)
                 ->where('documents.per_page', 10)
                 ->where('documents.current_page', 1)
                 ->has('documents.data', 5);
        });
    }
    
    /**
     * Test pagination with multiple pages and navigation
     */
    public function test_pagination_navigates_to_second_page()
    {
        $user = User::factory()->create();
        
        // Create more documents than fit on one page (10 per page)
        Document::factory()->count(15)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/documents?page=2');

        $response->assertStatus(200);
        
        // Check that second page shows the remaining documents
        $response->assertInertia(function (Assert $page) {
            $page->component('Documents/Index')
                 ->where('documents.total', 15)
                 ->where('documents.from', 11)
                 ->where('documents.to', 15)
                 ->where('documents.per_page', 10)
                 ->where('documents.current_page', 2)
                 ->has('documents.data', 5);
        });
    }
}