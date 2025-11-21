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
        Schema::create('food_database', function (Blueprint $table) {
            $table->id();
            $table->string('food_name');
            $table->string('category');
            $table->integer('calories_per_100g');
            $table->decimal('protein_per_100g', 5, 2);
            $table->decimal('carbs_per_100g', 5, 2);
            $table->decimal('fat_per_100g', 5, 2);
            $table->decimal('fiber', 5, 2)->nullable();
            $table->decimal('sodium', 8, 2)->nullable();
            $table->string('standard_unit')->default('gram');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_database');
    }
};
