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
        Schema::table('ai_feedbacks', function (Blueprint $table) {
            $table->jsonb('foods_to_avoid')->nullable()->after('suggested_foods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_feedbacks', function (Blueprint $table) {
            $table->dropColumn('foods_to_avoid');
        });
    }
};
