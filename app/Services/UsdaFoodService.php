<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UsdaFoodService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.nal.usda.gov/fdc/v1';

    public function __construct()
    {
        $this->apiKey = env('USDA_API_KEY');
    }

    protected function client()
    {
        return Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
        ]);
    }

    public function search($query)
    {
        $response = $this->client()->get("{$this->baseUrl}/foods/search", [
            'query' => $query,
            'pageSize' => 20,
            'dataType' => ['Foundation', 'SR Legacy', 'Branded'],
        ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();

        return collect($data['foods'] ?? [])->map(function ($item) {
            return $this->mapToInternalFormat($item);
        });
    }

    public function getFood($fdcId)
    {
        $response = $this->client()->get("{$this->baseUrl}/food/{$fdcId}");

        if ($response->failed()) {
            return null;
        }

        return $this->mapToInternalFormat($response->json());
    }

    public function getFoods(array $fdcIds)
    {
        $response = $this->client()->post("{$this->baseUrl}/foods", [
            'fdcIds' => $fdcIds,
            'format' => 'abridged', // or 'full'
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())->map(function ($item) {
            return $this->mapToInternalFormat($item);
        });
    }

    public function listFoods($params = [])
    {
        $response = $this->client()->get("{$this->baseUrl}/foods/list", array_merge([
            'pageSize' => 20,
            'dataType' => ['Foundation', 'SR Legacy'],
        ], $params));

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())->map(function ($item) {
            return $this->mapToInternalFormat($item);
        });
    }

    protected function mapToInternalFormat($item)
    {
        $nutrients = collect($item['foodNutrients']);

        // Helper to find nutrient value by nutrientId or name
        // Note: Structure might differ slightly between endpoints (search vs details)
        // Search: nutrientId, value
        // Details: nutrient: { id: ... }, amount
        
        $getValue = function ($nutrientIds) use ($nutrients, $item) {
            $nutrient = $nutrients->first(function ($n) use ($nutrientIds) {
                // Handle different structures
                $id = $n['nutrientId'] ?? ($n['nutrient']['id'] ?? 0);
                return in_array($id, (array)$nutrientIds);
            });
            
            return $nutrient['value'] ?? ($nutrient['amount'] ?? 0);
        };

        return [
            'id' => null,
            'food_name' => $item['description'],
            'category' => $item['foodCategory']['description'] ?? ($item['foodCategory'] ?? 'General'),
            'calories_per_100g' => $getValue(1008),
            'protein_per_100g' => $getValue(1003),
            'carbs_per_100g' => $getValue(1005),
            'fat_per_100g' => $getValue(1004),
            'fiber' => $getValue(1079),
            'sodium' => $getValue(1093),
            'standard_unit' => 'gram',
            'source' => 'USDA',
            'external_id' => $item['fdcId']
        ];
    }
}
