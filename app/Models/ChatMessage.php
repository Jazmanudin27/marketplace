<?php

namespace App\Models;

use App\Models\ChatConversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'tenant_id',
        'chat_conversation_id',
        'platform_message_id',
        'direction',
        'sender_role',
        'message_type',
        'body',
        'media_url',
        'delivery_status',
        'sent_at',
        'read_at',
        'payload',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'payload' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }
}
