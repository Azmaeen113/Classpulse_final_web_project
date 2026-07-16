<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Minimal login accounts only — no classrooms, quizzes, or live sessions.
 */
class LoginAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';

        $accounts = [
            ['name' => 'ClassPulse Admin', 'email' => 'admin@classpulse.test', 'role' => 'admin'],
            ['name' => 'Teacher One', 'email' => 'teacher1@classpulse.test', 'role' => 'teacher'],
            ['name' => 'Student One', 'email' => 'student1@classpulse.test', 'role' => 'student'],
        ];

        foreach ($accounts as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'role' => $data['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
