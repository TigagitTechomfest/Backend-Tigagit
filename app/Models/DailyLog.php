<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'meal_entries',
        'total_daily_intake',
        'ai_feedback_generated',
    ];

    protected $casts = [
        'log_date' => 'date',
        'meal_entries' => 'array',
        'total_daily_intake' => 'array',
        'ai_feedback_generated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiFeedbacks()
    {
        return $this->hasMany(AiFeedback::class, 'log_id');
    }
}
