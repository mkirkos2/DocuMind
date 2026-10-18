<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_route_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/documents/search');
        $response->assertStatus(200);
    }
}