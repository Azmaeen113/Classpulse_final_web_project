<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedInteger('default_time_limit_seconds')->default(30)->after('is_published');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->unsignedInteger('time_bonus_seconds')->default(0)->after('question_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('default_time_limit_seconds');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn('time_bonus_seconds');
        });
    }
};
