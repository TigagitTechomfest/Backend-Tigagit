<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'message',
        'sender',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
