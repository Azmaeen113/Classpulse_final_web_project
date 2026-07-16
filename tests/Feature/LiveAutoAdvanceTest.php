<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\LiveSessionAdvanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveAutoAdvanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_advances_when_all_students_have_answered(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Solo Class',
            'room_code' => 'SOLO01',
            'is_active' => true,
        ]);
        $classroom->students()->attach($student->id, ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Auto Advance',
            'is_published' => true,
            'default_time_limit_seconds' => 30,
        ]);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'First?',
            'points' => 100,
            'time_limit_seconds' => 600,
            'sort_order' => 0,
            'short_answer_match' => 'exact',
        ]);
        QuestionOption::create([
            'question_id' => $q1->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 0,
        ]);
        QuestionOption::create([
            'question_id' => $q1->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 1,
        ]);

        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'Second?',
            'points' => 100,
            'time_limit_seconds' => 600,
            'sort_order' => 1,
            'short_answer_match' => 'exact',
        ]);
        QuestionOption::create([
            'question_id' => $q2->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 0,
        ]);
        QuestionOption::create([
            'question_id' => $q2->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 1,
        ]);

        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'current_question_id' => $q1->id,
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'started_at' => now(),
        ]);

        $opt = $q1->options()->where('is_correct', true)->value('id');

        // Student must be marked as an active live participant first.
        $this->actingAs($student)->get(route('student.live.show', $session))->assertOk();

        $response = $this->actingAs($student)->postJson(route('student.live.answer', $session), [
            'question_id' => $q1->id,
            'selected_option_id' => $opt,
        ]);

        $response->assertOk()->assertJsonPath('advanced', true);

        $session->refresh();
        $this->assertSame($q2->id, (int) $session->current_question_id);
        $this->assertSame('active', $session->status);
    }

    public function test_advances_when_timer_expires(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Timer Class',
            'room_code' => 'TIME01',
            'is_active' => true,
        ]);
        $classroom->students()->attach($student->id, ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Timer Quiz',
            'is_published' => true,
            'default_time_limit_seconds' => 5,
        ]);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'A?',
            'points' => 100,
            'time_limit_seconds' => 5,
            'sort_order' => 0,
            'short_answer_expected' => 'a',
            'short_answer_match' => 'exact',
        ]);
        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'B?',
            'points' => 100,
            'time_limit_seconds' => 5,
            'sort_order' => 1,
            'short_answer_expected' => 'b',
            'short_answer_match' => 'exact',
        ]);

        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'current_question_id' => $q1->id,
            'question_started_at' => now()->subSeconds(30),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'started_at' => now()->subSeconds(30),
        ]);

        app(LiveSessionAdvanceService::class)->advanceIfNeeded($session);

        $session->refresh();
        $this->assertSame($q2->id, (int) $session->current_question_id);
    }
}
