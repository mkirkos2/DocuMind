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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->text('chunk_text');
            $table->unsignedInteger('chunk_order');
            $table->unsignedInteger('character_count');
            $table->unsignedInteger('token_count')->nullable();
            $table->unsignedInteger('page_number')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->index('document_id');
            $table->index(['document_id', 'chunk_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
