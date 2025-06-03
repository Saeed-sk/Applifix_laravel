<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestApiRequest extends Model
{
    protected $fillable = [
        'ip', 'endpoint', 'request_count', 'last_request_at'
    ];

    protected $casts = [
        'last_request_at' => 'datetime',
    ];


    protected array $dates = ['last_request_at'];
}
