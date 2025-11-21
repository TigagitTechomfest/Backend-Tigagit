<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiFeedback extends Model
{
    use HasFactory;

    protected $table = 'ai_feedbacks';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'log_id',
        'feedback_type',
        'feedback_message',
        'suggested_foods',
        'suggested_exercises',
        'macro_analysis',
        'generated_at',
    ];

    protected $casts = [
        'suggested_foods' => 'array',
        'suggested_exercises' => 'array',
        'macro_analysis' => 'array',
        'generated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dailyLog()
    {
        return $this->belongsTo(DailyLog::class, 'log_id');
    }
}
