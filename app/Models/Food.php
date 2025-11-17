<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{
    protected $fillable = [
        'name',
        'serving_size_grams',
        'calories',
        'protein',
        'carbs',
        'fats',
        'fiber',
    ];

    protected function casts(): array
    {
        return [
            'serving_size_grams' => 'decimal:2',
            'calories' => 'decimal:2',
            'protein' => 'decimal:2',
            'carbs' => 'decimal:2',
            'fats' => 'decimal:2',
            'fiber' => 'decimal:2',
        ];
    }

    /**
     * Get the food logs for the food.
     */
    public function foodLogs(): HasMany
    {
        return $this->hasMany(FoodLog::class);
    }

    /**
     * Get the recipe ingredients for the food.
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
