<?php

namespace Database\Factories;

use App\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentChunk>
 */
class DocumentChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => \App\Models\Document::factory(),
            'chunk_text' => fake()->paragraph(),
            'chunk_order' => fake()->numberBetween(0, 10),
            'character_count' => fake()->numberBetween(100, 1000),
            'token_count' => fake()->numberBetween(50, 500),
            'page_number' => fake()->optional()->numberBetween(1, 100),
        ];
    }
}
