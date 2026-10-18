<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageChunkReference extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'document_chunk_id',
        'relevance_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relevance_score' => 'decimal:2',
        ];
    }

    /**
     * Get the message that owns the reference.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the document chunk that owns the reference.
     */
    public function documentChunk(): BelongsTo
    {
        return $this->belongsTo(DocumentChunk::class);
    }
}
