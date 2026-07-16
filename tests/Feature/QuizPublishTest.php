<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_publish_entire_quiz_in_one_click(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Pub Class',
            'room_code' => 'PUB001',
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Publish Me',
            'is_published' => false,
            'default_time_limit_seconds' => 30,
        ]);
        Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => '2+2?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_expected' => '4',
            'short_answer_match' => 'exact',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.quizzes.publish', $quiz))
            ->assertRedirect();

        $this->assertTrue($quiz->fresh()->is_published);
    }

    public function test_start_live_auto_publishes_draft_quiz(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Auto Pub',
            'room_code' => 'AUTO01',
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Draft Live',
            'is_published' => false,
            'default_time_limit_seconds' => 30,
        ]);
        Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'Hi?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_expected' => 'Hello',
            'short_answer_match' => 'exact',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.live.start', $quiz))
            ->assertRedirect();

        $this->assertTrue($quiz->fresh()->is_published);
        $this->assertDatabaseHas('quiz_sessions', [
            'quiz_id' => $quiz->id,
            'status' => 'active',
        ]);
    }

    public function test_starting_live_ends_previous_open_sessions_for_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'One At A Time',
            'room_code' => 'ONE001',
            'is_active' => true,
        ]);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Restart Safe',
            'is_published' => true,
            'default_time_limit_seconds' => 30,
        ]);
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'Hi?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_expected' => 'Hello',
            'short_answer_match' => 'exact',
        ]);

        $stale = \App\Models\QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'current_question_id' => $question->id,
            'question_started_at' => now()->subMinutes(10),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'started_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.live.start', $quiz))
            ->assertRedirect();

        $this->assertSame('ended', $stale->fresh()->status);
        $this->assertSame(
            1,
            \App\Models\QuizSession::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', 'active')
                ->count()
        );
    }
}
