<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodDatabase extends Model
{
    use HasFactory;

    protected $table = 'food_database';

    protected $fillable = [
        'food_name',
        'category',
        'calories_per_100g',
        'protein_per_100g',
        'carbs_per_100g',
        'fat_per_100g',
        'fiber',
        'sodium',
        'standard_unit',
    ];
}
