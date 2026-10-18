<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test to verify the test system works
     */
    public function test_it_runs_document_tests()
    {
        $this->assertTrue(true);
    }

    /**
     * Test that authenticated users can view the documents index
     */
    public function test_user_can_view_documents_index()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertStatus(200);
    }
}