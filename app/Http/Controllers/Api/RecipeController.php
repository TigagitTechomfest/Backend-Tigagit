<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * Get all recipes for authenticated user
     */
    public function index(Request $request)
    {
        $recipes = Recipe::where('user_id', $request->user()->id)
            ->with('ingredients.food')
            ->paginate(20);

        return response()->json($recipes);
    }

    /**
     * Store a new recipe
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.food_id' => 'required|exists:foods,id',
            'ingredients.*.quantity_grams' => 'required|numeric|min:0.01',
        ]);

        $recipe = Recipe::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'instructions' => $request->instructions,
        ]);

        // Add ingredients if provided
        if ($request->has('ingredients')) {
            foreach ($request->ingredients as $ingredient) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'food_id' => $ingredient['food_id'],
                    'quantity_grams' => $ingredient['quantity_grams'],
                ]);
            }
        }

        $recipe->load('ingredients.food');

        return response()->json([
            'message' => 'Recipe created successfully',
            'recipe' => $recipe,
        ], 201);
    }

    /**
     * Get a specific recipe
     */
    public function show(Request $request, Recipe $recipe)
    {
        // Ensure user owns this recipe
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $recipe->load('ingredients.food');

        return response()->json($recipe);
    }

    /**
     * Update a recipe
     */
    public function update(Request $request, Recipe $recipe)
    {
        // Ensure user owns this recipe
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $recipe->update($request->only([
            'name',
            'description',
            'instructions',
        ]));

        $recipe->load('ingredients.food');

        return response()->json([
            'message' => 'Recipe updated successfully',
            'recipe' => $recipe,
        ]);
    }

    /**
     * Delete a recipe
     */
    public function destroy(Request $request, Recipe $recipe)
    {
        // Ensure user owns this recipe
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $recipe->delete();

        return response()->json([
            'message' => 'Recipe deleted successfully',
        ]);
    }

    /**
     * Add ingredient to recipe
     */
    public function addIngredient(Request $request, Recipe $recipe)
    {
        // Ensure user owns this recipe
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'food_id' => 'required|exists:foods,id',
            'quantity_grams' => 'required|numeric|min:0.01',
        ]);

        $ingredient = RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'food_id' => $request->food_id,
            'quantity_grams' => $request->quantity_grams,
        ]);

        $ingredient->load('food');

        return response()->json([
            'message' => 'Ingredient added successfully',
            'ingredient' => $ingredient,
        ], 201);
    }

    /**
     * Remove ingredient from recipe
     */
    public function removeIngredient(Request $request, Recipe $recipe, $ingredient)
    {
        // Ensure user owns this recipe
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ingredient = RecipeIngredient::where('recipe_id', $recipe->id)
            ->findOrFail($ingredient);

        $ingredient->delete();

        return response()->json([
            'message' => 'Ingredient removed successfully',
        ]);
    }
}
