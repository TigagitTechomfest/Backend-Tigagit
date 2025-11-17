<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthProfile;
use Illuminate\Http\Request;

class HealthProfileController extends Controller
{
    /**
     * Store or update health profile
     */
    public function store(Request $request)
    {
        $request->validate([
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'activity_level' => 'required|in:sedentary,light,moderate,active',
            'goal' => 'required|in:lose_weight,maintain,gain_weight',
        ]);

        $healthProfile = HealthProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->only([
                'date_of_birth',
                'gender',
                'weight_kg',
                'height_cm',
                'activity_level',
                'goal',
            ])
        );

        return response()->json([
            'message' => 'Health profile saved successfully',
            'health_profile' => $healthProfile,
        ], 201);
    }

    /**
     * Update health profile
     */
    public function update(Request $request, HealthProfile $healthProfile)
    {
        // Ensure user owns this health profile
        if ($healthProfile->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'activity_level' => 'sometimes|in:sedentary,light,moderate,active',
            'goal' => 'sometimes|in:lose_weight,maintain,gain_weight',
        ]);

        $healthProfile->update($request->only([
            'date_of_birth',
            'gender',
            'weight_kg',
            'height_cm',
            'activity_level',
            'goal',
        ]));

        return response()->json([
            'message' => 'Health profile updated successfully',
            'health_profile' => $healthProfile,
        ]);
    }

    /**
     * Get health profile
     */
    public function show(Request $request)
    {
        $healthProfile = HealthProfile::where('user_id', $request->user()->id)->first();

        if (!$healthProfile) {
            return response()->json([
                'message' => 'Health profile not found',
            ], 404);
        }

        return response()->json([
            'health_profile' => $healthProfile,
        ]);
    }
}
