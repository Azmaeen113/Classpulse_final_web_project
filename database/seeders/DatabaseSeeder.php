<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates login accounts only (no classrooms / quizzes / live sessions).
     * Full demo classroom pack (optional):
     *   php artisan db:seed --class=ClassPulseSeeder
     */
    public function run(): void
    {
        $this->call([
            LoginAccountsSeeder::class,
        ]);
    }
}
