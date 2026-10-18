<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_chunk_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('document_chunk_id');
            $table->decimal('relevance_score', 3, 2); // 0.00 to 1.00
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('document_chunk_id')->references('id')->on('document_chunks')->onDelete('cascade');
            $table->unique(['message_id', 'document_chunk_id']);
            $table->index('message_id');
            $table->index('document_chunk_id');
            $table->index(['message_id', 'relevance_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_chunk_references');
    }
};
