<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    /** @use HasFactory<\Database\Factories\TopicFactory> */
    use HasFactory;

    protected $guarded = 'id';
    protected $fillable = [
        'title',
        'description',
        'src'
    ];

    public function chats():HasMany
    {
        return $this->hasMany(Chat::class , 'topic_id' , 'id');
    }


}
