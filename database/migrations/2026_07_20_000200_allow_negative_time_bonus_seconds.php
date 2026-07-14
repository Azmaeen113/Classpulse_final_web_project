<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            // Allow negative bonuses so teachers can shorten live timers below base duration.
            $table->integer('time_bonus_seconds')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->unsignedInteger('time_bonus_seconds')->default(0)->change();
        });
    }
};
