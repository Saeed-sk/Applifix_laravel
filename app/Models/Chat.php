<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    /** @use HasFactory<\Database\Factories\ChatFactory> */
    use HasFactory;

    protected $fillable = [
        'id',
        'title',
        'user_id',
        'topic_id',
    ];

    public function history():HasMany
    {
        return $this->hasMany(ChatHistory::class , 'chat_id' , 'id');
    }

    public function topic():BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'id');
    }

    public function user():BelongsTo
    {
        $this->belongsTo(User::class, 'user_id', 'id');
    }
}
