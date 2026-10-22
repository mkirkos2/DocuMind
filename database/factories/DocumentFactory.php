<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Will be set in tests
            'title' => fake()->sentence(),
            'original_filename' => fake()->word() . '.txt',
            'file_path' => 'documents/' . fake()->uuid() . '.txt',
            'file_size' => fake()->numberBetween(100, 10000),
            'mime_type' => 'text/plain',
            'processing_status' => 'completed',
            'processing_error' => null,
            'chunk_count' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Set the document as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'pending',
        ]);
    }

    /**
     * Set the document as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'failed',
            'processing_error' => 'Document processing failed',
        ]);
    }
}
