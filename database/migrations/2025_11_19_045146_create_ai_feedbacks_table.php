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
        Schema::create('ai_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('log_id')->nullable()->constrained('daily_logs')->onDelete('cascade');
            $table->enum('feedback_type', ['meal', 'daily', 'weekly']);
            $table->text('feedback_message');
            $table->jsonb('suggested_foods')->nullable();
            $table->jsonb('suggested_exercises')->nullable();
            $table->jsonb('macro_analysis')->nullable();
            $table->timestamp('generated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_feedbacks');
    }
};
