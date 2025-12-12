<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
    public function __construct()
    {
        // Middleware is handled in routes
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            
            // Health Assessment Data (Required for onboarding flow)
            'age' => 'required|integer|min:15|max:65',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric|min:50|max:300', 
            'weight' => 'required|numeric|min:20|max:500', 
            'activity_level' => 'required|string',
            'health_goal' => 'required|string',
            'dietary_preference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        // 1. Calculate BMI
        $heightM = $request->height / 100;
        $bmi = $request->weight / ($heightM * $heightM);

        // 2. Validate Goal based on BMI (Strict Rule)
        if ($bmi >= 25 && in_array($request->health_goal, ['Weight Gain', 'Build Muscle', 'BULKING'])) {
             return response()->json(['success' => false, 'message' => 'Invalid Goal: You cannot choose Weight Gain/Bulking when Overweight.'], 400);
        }
        if ($bmi < 18.5 && in_array($request->health_goal, ['Weight Loss', 'CUTTING'])) {
             return response()->json(['success' => false, 'message' => 'Invalid Goal: You cannot choose Weight Loss/Cutting when Underweight.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Calculate BMR (Harris-Benedict)
            if ($request->gender == 'male') {
                $bmr = 88.362 + (13.397 * $request->weight) + (4.799 * $request->height) - (5.677 * $request->age);
            } else {
                $bmr = 447.593 + (9.247 * $request->weight) + (3.098 * $request->height) - (4.330 * $request->age);
            }

            $activityMultipliers = [
                'Sedentary' => 1.2,
                'Light' => 1.375,
                'Moderate' => 1.55,
                'Very Active' => 1.725,
            ];
            $multiplier = $activityMultipliers[$request->activity_level] ?? 1.2;
            $tdee = $bmr * $multiplier;

            $goalAdjustments = [
                'Weight Loss' => -500,
                'CUTTING' => -500,
                'Maintain' => 0,
                'MAINTAIN' => 0,
                'Weight Gain' => 500,
                'BULKING' => 500,
                'Build Muscle' => 250,
            ];
            
            $adjustment = $goalAdjustments[$request->health_goal] ?? 0;
            $dailyCalories = $tdee + $adjustment;

            $protein = ($dailyCalories * 0.25) / 4;
            $carbs = ($dailyCalories * 0.50) / 4;
            $fat = ($dailyCalories * 0.25) / 9;

            \App\Models\HealthAssessment::create([
                'user_id' => $user->id,
                'age' => $request->age,
                'gender' => $request->gender,
                'height' => $request->height,
                'weight' => $request->weight,
                'initial_weight' => $request->weight,
                'bmi' => round($bmi, 2),
                'activity_level' => $request->activity_level,
                'health_goal' => $request->health_goal,
                'dietary_preference' => $request->dietary_preference,
                'daily_calorie_target' => round($dailyCalories),
                'daily_protein_target' => round($protein),
                'daily_carbs_target' => round($carbs),
                'daily_fat_target' => round($fat),
            ]);

            \App\Models\HealthHistory::create([
                'user_id' => $user->id,
                'history_date' => now()->toDateString(),
                'weight' => $request->weight,
                'bmi' => round($bmi, 2),
                'health_status' => 'Initial Assessment',
            ]);

            \Illuminate\Support\Facades\DB::commit();

            $token = Auth::guard('api')->login($user);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user' => $user,
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();
        $user->profile_image_url = $user->profile_image ? asset('storage/' . $user->profile_image) : null;
        return response()->json(['success' => true, 'message' => 'User profile', 'data' => $user]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json(['success' => true, 'message' => 'Successfully logged out', 'data' => null]);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user();
        $user->profile_image_url = $user->profile_image ? asset('storage/' . $user->profile_image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Token generated',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
                'user' => $user
            ]
        ]);
    }
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        // Append full URL to response
        $user->profile_image_url = $user->profile_image ? asset('storage/' . $user->profile_image) : null;

        return response()->json(['success' => true, 'message' => 'Profile updated successfully', 'data' => $user]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Email not found', 'data' => $validator->errors()], 404);
        }

        $token = \Illuminate\Support\Str::random(60);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Send Email
        try {
            $frontendUrl = env('FRONTEND_URL');
            $resetLink = $frontendUrl . "/reset-password?token=" . $token;
            
            // Passing token to mail, assuming mail view constructs the link or we pass the link
            // For now, let's keep passing the token as the Mail class expects, 
            // but we can update the Mail class to accept the full link if needed.
            // However, the user specifically asked about the link *generation*.
            // The Mail class currently uses {{ $token }} to build the link in the view.
            // So we should update the VIEW to use the env variable or pass the full link.
            
            Mail::to($request->email)->send(new ResetPasswordMail($token, $request->email));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset password link sent to your email',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed', // requires password_confirmation field
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'data' => $validator->errors()], 400);
        }

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        // Check expiration (15 minutes)
        if (\Carbon\Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Token expired'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['success' => true, 'message' => 'Password reset successfully']);
    }
}
