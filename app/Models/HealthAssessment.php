<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age',
        'gender',
        'height',
        'weight',
        'initial_weight',
        'bmi',
        'activity_level',
        'health_goal',
        'dietary_preference',
        'daily_calorie_target',
        'daily_protein_target',
        'daily_carbs_target',
        'daily_fat_target',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
