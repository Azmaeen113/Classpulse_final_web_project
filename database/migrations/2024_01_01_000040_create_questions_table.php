<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('type'); // mcq|true_false|short_answer
            $table->text('prompt');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('points')->default(100);
            $table->unsignedInteger('time_limit_seconds')->default(30);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('short_answer_expected')->nullable();
            $table->string('short_answer_match')->default('exact'); // exact|contains
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
