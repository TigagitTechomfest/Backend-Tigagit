<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodDatabase;
use Illuminate\Support\Facades\Validator;

use App\Services\UsdaFoodService;

class FoodDatabaseController extends Controller
{
    protected $usdaService;

    public function __construct(UsdaFoodService $usdaService)
    {
        $this->usdaService = $usdaService;
    }

    public function index(Request $request)
    {
        // 1. Local Search First
        $query = FoodDatabase::query();

        if ($request->has('search')) {
            $query->where('food_name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $foods = $query->paginate(20);

        // 2. If local search is empty AND search term is provided, try USDA
        if ($foods->isEmpty() && $request->has('search')) {
            $usdaFoods = $this->usdaService->search($request->search);
            
            // Save fetched foods to local DB to build up the database
            foreach ($usdaFoods as $foodData) {
                // Check if already exists by external_id to avoid duplicates
                FoodDatabase::firstOrCreate(
                    ['external_id' => $foodData['external_id']],
                    $foodData
                );
            }

            // Re-run local search to include newly added items
            $foods = FoodDatabase::where('food_name', 'like', '%' . $request->search . '%')->paginate(20);
            
            return response()->json(['success' => true, 'message' => 'Foods retrieved from USDA and saved locally', 'data' => $foods]);
        }

        return response()->json(['success' => true, 'message' => 'Foods retrieved', 'data' => $foods]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'food_name' => 'required|string',
            'category' => 'required|string',
            'calories_per_100g' => 'required|integer',
            'protein_per_100g' => 'required|numeric',
            'carbs_per_100g' => 'required|numeric',
            'fat_per_100g' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        $food = FoodDatabase::create($request->all());

        return response()->json(['success' => true, 'message' => 'Food created', 'data' => $food]);
    }

    public function show(Request $request, $id)
    {
        // 1. Try to find in local database first
        $food = FoodDatabase::find($id);
        
        if ($food) {
            return response()->json(['success' => true, 'message' => 'Food details', 'data' => $food]);
        }

        // 2. If not found locally, try to find in USDA by external_id (if $id is treated as fdcId) 
        // OR if the user explicitly requested 'source=online' or passed an FDC ID.
        // Assuming $id could be an FDC ID if not found in local DB.
        
        $usdaFood = $this->usdaService->getFood($id);

        if ($usdaFood) {
            // 3. Save to local database for future use
            $newFood = FoodDatabase::create($usdaFood);
            return response()->json(['success' => true, 'message' => 'Food details fetched from USDA and saved', 'data' => $newFood]);
        }

        return response()->json(['success' => false, 'message' => 'Food not found', 'data' => null], 404);
    }

    public function list(Request $request)
    {
        if ($request->query('source') === 'online') {
            $foods = $this->usdaService->listFoods($request->all());
            return response()->json(['success' => true, 'message' => 'Food list from USDA', 'data' => $foods]);
        }
        
        // Local list is basically index without search, or we can just reuse index
        return $this->index($request);
    }

    public function batchDetails(Request $request)
    {
        $request->validate([
            'fdcIds' => 'required|array',
            'fdcIds.*' => 'integer'
        ]);

        $foods = $this->usdaService->getFoods($request->fdcIds);
        return response()->json(['success' => true, 'message' => 'Batch food details from USDA', 'data' => $foods]);
    }
}
