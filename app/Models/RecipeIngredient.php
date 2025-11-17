<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'food_id',
        'quantity_grams',
    ];

    protected function casts(): array
    {
        return [
            'quantity_grams' => 'decimal:2',
        ];
    }

    /**
     * Get the recipe that owns the ingredient.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the food for the ingredient.
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
