<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatHistory extends Model
{
    /** @use HasFactory<\Database\Factories\ChatHistoryFactory> */
    use HasFactory;

    protected $guarded = ['id'];
    protected $fillable = [
        'id',
        'chat_id',
        'user_id',
        'message',
        'role'
    ];

    public function chat():BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
