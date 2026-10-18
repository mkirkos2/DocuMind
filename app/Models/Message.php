<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the document references for the message.
     */
    public function documentReferences(): HasMany
    {
        return $this->hasMany(MessageDocumentReference::class);
    }

    /**
     * Get the chunk references for the message.
     */
    public function chunkReferences(): HasMany
    {
        return $this->hasMany(MessageChunkReference::class);
    }

    /**
     * Get the feedback for the message.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
