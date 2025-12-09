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
        Schema::table('health_assessments', function (Blueprint $table) {
            $table->decimal('initial_weight', 5, 2)->after('weight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_assessments', function (Blueprint $table) {
            $table->dropColumn('initial_weight');
        });
    }
};
