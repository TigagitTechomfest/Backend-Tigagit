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
        // If 'source=online' is specified, search USDA API directly
        if ($request->query('source') === 'online' && $request->has('search')) {
            $foods = $this->usdaService->search($request->search);
            return response()->json(['success' => true, 'message' => 'Foods retrieved from USDA', 'data' => $foods]);
        }

        // Default: Local Search
        $query = FoodDatabase::query();

        if ($request->has('search')) {
            $query->where('food_name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $foods = $query->paginate(20);

        // If local search returns empty and search term exists, optionally fallback to USDA
        // But for now, let's keep it explicit via 'source' param to avoid rate limits on every typo
        
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
        // Check if it's a request for online data
        if ($request->query('source') === 'online') {
            $food = $this->usdaService->getFood($id);
            if (!$food) {
                return response()->json(['success' => false, 'message' => 'Food not found in USDA', 'data' => null], 404);
            }
            return response()->json(['success' => true, 'message' => 'Food details from USDA', 'data' => $food]);
        }

        // Local search
        $food = FoodDatabase::find($id);
        if (!$food) {
            return response()->json(['success' => false, 'message' => 'Food not found', 'data' => null], 404);
        }

        return response()->json(['success' => true, 'message' => 'Food details', 'data' => $food]);
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
