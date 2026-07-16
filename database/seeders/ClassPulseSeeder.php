<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClassPulseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $admin = User::create([
            'name' => 'ClassPulse Admin',
            'email' => 'admin@classpulse.test',
            'password' => $password,
            'role' => 'admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $teacher1 = User::create([
            'name' => 'Teacher One',
            'email' => 'teacher1@classpulse.test',
            'password' => $password,
            'role' => 'teacher',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $teacher2 = User::create([
            'name' => 'Teacher Two',
            'email' => 'teacher2@classpulse.test',
            'password' => $password,
            'role' => 'teacher',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $students = collect();
        for ($i = 1; $i <= 10; $i++) {
            $students->push(User::create([
                'name' => "Student {$i}",
                'email' => "student{$i}@classpulse.test",
                'password' => $password,
                'role' => 'student',
                'email_verified_at' => now(),
                'is_active' => true,
            ]));
        }

        $classroom = Classroom::create([
            'teacher_id' => $teacher1->id,
            'name' => 'Intro to Web Programming',
            'subject' => 'Computer Science',
            'room_code' => 'CPULSE',
            'qr_payload' => url('/student/join?code=CPULSE'),
            'is_active' => true,
        ]);

        foreach ($students as $student) {
            $classroom->students()->attach($student->id, ['joined_at' => now()->subDays(rand(1, 14))]);
        }

        $quiz = Quiz::create([
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher1->id,
            'title' => 'HTTP & Laravel Basics',
            'description' => 'A mixed warm-up quiz covering fundamentals.',
            'is_published' => true,
        ]);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'mcq',
            'prompt' => 'Which HTTP method is typically used to create a resource?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
        ]);
        $this->options($q1, ['GET', 'POST', 'PUT', 'DELETE'], 1);

        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'Laravel Blade templates are rendered on the server.',
            'points' => 100,
            'time_limit_seconds' => 20,
            'sort_order' => 1,
        ]);
        $this->options($q2, ['True', 'False'], 0);

        $q3 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'mcq',
            'prompt' => 'Where do you define web routes in a Laravel app?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 2,
        ]);
        $this->options($q3, ['routes/web.php', 'public/index.php', 'config/app.php', 'artisan'], 0);

        $q4 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'Fill in the blank: The HTTP method used to create a resource is ____.',
            'points' => 100,
            'time_limit_seconds' => 40,
            'sort_order' => 3,
            // Case-sensitive exact match — "post" or "Post" would be incorrect
            'short_answer_expected' => 'POST',
            'short_answer_match' => 'exact',
        ]);

        $q5 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'mcq',
            'prompt' => 'Which tool compiles Laravel front-end assets by default?',
            'points' => 100,
            'time_limit_seconds' => 25,
            'sort_order' => 4,
        ]);
        $this->options($q5, ['Webpack only', 'Vite', 'Gulp', 'Parcel'], 1);

        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher1->id,
            'status' => 'ended',
            'current_question_id' => $q5->id,
            'question_started_at' => now()->subMinutes(5),
            'reveal_answer' => true,
            'started_at' => now()->subMinutes(20),
            'ended_at' => now()->subMinutes(2),
        ]);

        $scoring = app(ScoringService::class);
        $questions = [$q1, $q2, $q3, $q4, $q5];

        foreach ($students->take(8) as $index => $student) {
            foreach ($questions as $qi => $question) {
                $responseTime = 2000 + ($index * 400) + ($qi * 300);
                $selectedOptionId = null;
                $shortAnswer = null;

                if (in_array($question->type, ['mcq', 'true_false'], true)) {
                    $correct = $question->options()->where('is_correct', true)->first();
                    $wrong = $question->options()->where('is_correct', false)->first();
                    $selectedOptionId = ($index + $qi) % 3 === 0 ? $wrong?->id : $correct?->id;
                } else {
                    $shortAnswer = ($index % 2 === 0) ? 'id' : 'uuid';
                }

                $result = $scoring->score($question, $responseTime, $selectedOptionId, $shortAnswer);

                SessionResponse::create([
                    'quiz_session_id' => $session->id,
                    'question_id' => $question->id,
                    'student_id' => $student->id,
                    'selected_option_id' => $selectedOptionId,
                    'short_answer_text' => $shortAnswer,
                    'is_correct' => $result['is_correct'],
                    'points_awarded' => $result['points_awarded'],
                    'response_time_ms' => $responseTime,
                    'is_auto_submit' => false,
                    'answered_at' => now()->subMinutes(15 - $qi),
                ]);
            }
        }

        Notification::create([
            'user_id' => $teacher1->id,
            'type' => 'classroom.join',
            'title' => 'Student joined',
            'body' => 'Student 1 joined Intro to Web Programming.',
            'payload' => ['classroom_id' => $classroom->id, 'student_id' => $students[0]->id],
        ]);

        Notification::create([
            'user_id' => $students[0]->id,
            'type' => 'session.results',
            'title' => 'Quiz results available',
            'body' => 'Results for “HTTP & Laravel Basics” are ready.',
            'payload' => ['quiz_session_id' => $session->id],
        ]);

        Notification::create([
            'user_id' => $admin->id,
            'type' => 'system',
            'title' => 'Seed complete',
            'body' => 'ClassPulse demo data was seeded.',
            'payload' => ['seeded' => true],
        ]);

        // Keep teacher2 referenced so unused-variable linters stay quiet in some IDEs
        unset($teacher2);
    }

    private function options(Question $question, array $texts, int $correctIndex): void
    {
        foreach ($texts as $i => $text) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $text,
                'is_correct' => $i === $correctIndex,
                'sort_order' => $i,
            ]);
        }
    }
}
