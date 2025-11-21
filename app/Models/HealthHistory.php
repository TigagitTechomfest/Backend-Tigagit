<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthHistory extends Model
{
    use HasFactory;

    protected $table = 'health_history';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'history_date',
        'weight',
        'bmi',
        'health_status',
        'recorded_at',
    ];

    protected $casts = [
        'history_date' => 'date',
        'recorded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
