<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'instructions',
    ];

    /**
     * Get the user that owns the recipe.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ingredients for the recipe.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
