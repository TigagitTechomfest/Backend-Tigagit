<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodLog extends Model
{
    protected $fillable = [
        'user_id',
        'food_id',
        'quantity_grams',
        'meal_type',
        'log_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity_grams' => 'decimal:2',
            'log_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the food log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the food for the food log.
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
