<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfile extends Model
{
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'weight_kg',
        'height_cm',
        'activity_level',
        'goal',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the health profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
