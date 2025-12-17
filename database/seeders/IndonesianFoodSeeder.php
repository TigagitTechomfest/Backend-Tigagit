<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\FoodDatabase;

class IndonesianFoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = base_path('data_pangan_lengkap.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File not found: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data) {
            $this->command->error("Failed to decode JSON.");
            return;
        }

        $count = 0;
        foreach ($data as $item) {
            // Helper to clean numeric values
            $cleanNumber = function ($val) {
                if (is_string($val)) {
                    $val = trim($val);
                    // Replace comma with dot if strictly needed, but JSON usually has dots or just numbers.
                    // Handle empty or non-numeric
                    if ($val === '' || $val === '-' || !is_numeric($val)) return 0;
                }
                return $val;
            };

            FoodDatabase::updateOrCreate(
                ['external_id' => $item['Code']],
                [
                    'food_name' => $item['Name'],
                    'category' => 'Indonesian', // Default category
                    'calories_per_100g' => (int) $cleanNumber($item['Energy']),
                    'protein_per_100g' => $cleanNumber($item['Protein']),
                    'carbs_per_100g' => $cleanNumber($item['Carbs']),
                    'fat_per_100g' => $cleanNumber($item['Fat']),
                    'fiber' => $cleanNumber($item['Fiber']),
                    'sodium' => $cleanNumber($item['Sodium']),
                    'standard_unit' => 'gram',
                    // 'external_id' is already in the search condition
                ]
            );
            $count++;
            
            if ($count % 100 == 0) {
                 $this->command->info("Processed $count records...");
            }
        }

        $this->command->info("Indonesian food data seeded successfully! Total: $count");
    }
}
