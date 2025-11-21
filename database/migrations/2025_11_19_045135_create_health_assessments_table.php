<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('age');
            $table->enum('gender', ['male', 'female']);
            $table->decimal('height', 5, 2); // cm
            $table->decimal('weight', 5, 2); // kg
            $table->decimal('bmi', 5, 2);
            $table->string('activity_level'); // Sedentary, Light, Moderate, Very Active
            $table->string('health_goal'); // Weight Loss, Maintain, Weight Gain, Build Muscle
            $table->string('dietary_preference')->nullable(); // Vegan, Vegetarian, Halal, Allergies
            $table->integer('daily_calorie_target');
            $table->integer('daily_protein_target');
            $table->integer('daily_carbs_target');
            $table->integer('daily_fat_target');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_assessments');
    }
};
