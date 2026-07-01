<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->text('short_answer_text')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->boolean('is_auto_submit')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_session_id', 'question_id', 'student_id'], 'session_question_student_unique');
            $table->index(['quiz_session_id', 'student_id'], 'session_student_leaderboard_idx');
            $table->index(['quiz_session_id', 'question_id'], 'session_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_responses');
    }
};
