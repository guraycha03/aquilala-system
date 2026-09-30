<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatHistory extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'messages',
        'ai_context',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'ai_context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}