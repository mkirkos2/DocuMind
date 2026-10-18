<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentChunk extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'chunk_text',
        'chunk_order',
        'character_count',
        'token_count',
        'page_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_order' => 'integer',
            'character_count' => 'integer',
            'token_count' => 'integer',
            'page_number' => 'integer',
        ];
    }

    /**
     * Get the document that owns the chunk.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the message references for the chunk.
     */
    public function messageReferences(): HasMany
    {
        return $this->hasMany(MessageChunkReference::class);
    }
}
