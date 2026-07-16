<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveAnswerIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private QuizSession $session;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create();

        $classroom = Classroom::create([
            'teacher_id' => $this->teacher->id,
            'name' => 'Test Class',
            'room_code' => 'TEST01',
            'is_active' => true,
        ]);
        $classroom->students()->attach($this->student->id, ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Integrity Quiz',
            'is_published' => true,
            'default_time_limit_seconds' => 30,
        ]);

        $this->question = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'The sky is blue.',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_match' => 'exact',
        ]);

        QuestionOption::create([
            'question_id' => $this->question->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 0,
        ]);
        QuestionOption::create([
            'question_id' => $this->question->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 1,
        ]);

        // Second question so answering Q1 does not end the whole session.
        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'Follow-up question.',
            'points' => 100,
            'time_limit_seconds' => 30,
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

        $this->session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $classroom->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
            'current_question_id' => $this->question->id,
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'started_at' => now(),
        ]);
    }

    public function test_poll_state_contract_includes_question_id_and_answered_flags(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson(route('api.poll.session-state', $this->session));

        $response->assertOk()
            ->assertJsonPath('current_question_id', $this->question->id)
            ->assertJsonPath('answered', false)
            ->assertJsonPath('already_answered', false)
            ->assertJsonPath('current_question.id', $this->question->id);
    }

    public function test_paused_session_rejects_answers(): void
    {
        $this->session->update(['status' => 'paused']);
        $optionId = $this->question->options()->where('is_correct', true)->value('id');

        $response = $this->actingAs($this->student)->postJson(
            route('student.live.answer', $this->session),
            [
                'question_id' => $this->question->id,
                'selected_option_id' => $optionId,
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('ok', false);
        $this->assertDatabaseCount('session_responses', 0);
    }

    public function test_correctness_hidden_until_reveal(): void
    {
        $optionId = $this->question->options()->where('is_correct', true)->value('id');

        $other = User::factory()->create();
        $this->session->classroom->students()->attach($other->id, ['joined_at' => now()]);
        $this->actingAs($other)->get(route('student.live.show', $this->session));
        $this->actingAs($this->student)->get(route('student.live.show', $this->session));

        $response = $this->actingAs($this->student)->postJson(
            route('student.live.answer', $this->session),
            [
                'question_id' => $this->question->id,
                'selected_option_id' => $optionId,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reveal_answer', false)
            ->assertJsonPath('is_correct', null)
            ->assertJsonPath('points_awarded', null);

        $this->assertDatabaseHas('session_responses', [
            'student_id' => $this->student->id,
            'is_correct' => 1,
        ]);
    }

    public function test_duplicate_answer_returns_friendly_response(): void
    {
        $optionId = $this->question->options()->where('is_correct', true)->value('id');

        // Stay on Q1: second classroom student keeps "all answered" from firing.
        $other = User::factory()->create();
        $this->session->classroom->students()->attach($other->id, ['joined_at' => now()]);
        $this->actingAs($other)->get(route('student.live.show', $this->session));
        $this->actingAs($this->student)->get(route('student.live.show', $this->session));

        $this->actingAs($this->student)->postJson(
            route('student.live.answer', $this->session),
            [
                'question_id' => $this->question->id,
                'selected_option_id' => $optionId,
            ]
        )->assertOk();

        $second = $this->actingAs($this->student)->postJson(
            route('student.live.answer', $this->session),
            [
                'question_id' => $this->question->id,
                'selected_option_id' => $optionId,
            ]
        );

        $second->assertOk()
            ->assertJsonPath('already_answered', true);
        $this->assertSame(1, SessionResponse::count());
    }

    public function test_late_answer_scores_zero_server_side(): void
    {
        $other = User::factory()->create();
        $this->session->classroom->students()->attach($other->id, ['joined_at' => now()]);
        $this->actingAs($other)->get(route('student.live.show', $this->session));
        $this->actingAs($this->student)->get(route('student.live.show', $this->session));

        $this->session->update([
            'question_started_at' => now()->subSeconds(90),
            'time_bonus_seconds' => 0,
        ]);
        $optionId = $this->question->options()->where('is_correct', true)->value('id');

        $this->actingAs($this->student)->postJson(
            route('student.live.answer', $this->session),
            [
                'question_id' => $this->question->id,
                'selected_option_id' => $optionId,
                'response_time_ms' => 100,
            ]
        )->assertOk();

        $row = SessionResponse::query()
            ->where('question_id', $this->question->id)
            ->where('student_id', $this->student->id)
            ->first();
        $this->assertNotNull($row);
        $this->assertFalse((bool) $row->is_correct);
        $this->assertSame(0, (int) $row->points_awarded);
        $this->assertTrue((bool) $row->is_auto_submit);
    }

    public function test_set_time_can_shorten_below_base(): void
    {
        $this->actingAs($this->teacher)->post(
            route('teacher.live.set-time', $this->session),
            ['seconds' => 10]
        )->assertRedirect();

        $this->session->refresh();
        $this->assertSame(-20, (int) $this->session->time_bonus_seconds);
        $this->assertSame(10, $this->session->effectiveTimeLimitSeconds($this->question));
    }

    public function test_next_question_resets_bonus(): void
    {
        $this->session->update(['time_bonus_seconds' => 45]);

        $before = (int) $this->session->current_question_id;

        $this->actingAs($this->teacher)
            ->post(route('teacher.live.next', $this->session))
            ->assertRedirect();

        $this->session->refresh();
        $this->assertSame(0, (int) $this->session->time_bonus_seconds);
        $this->assertNotSame($before, (int) $this->session->current_question_id);
        $this->assertSame('active', $this->session->status);
    }

    public function test_next_advances_through_all_questions_before_ending(): void
    {
        $quiz = $this->session->quiz;
        $ids = $quiz->questions()->orderBy('sort_order')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values();

        // Already on first question — advance through the rest.
        for ($i = 1; $i < $ids->count(); $i++) {
            $this->actingAs($this->teacher)
                ->post(route('teacher.live.next', $this->session))
                ->assertRedirect();
            $this->session->refresh();
            $this->assertSame('active', $this->session->status);
            $this->assertSame($ids[$i], (int) $this->session->current_question_id);
        }

        // Final next ends the session
        $this->actingAs($this->teacher)
            ->post(route('teacher.live.next', $this->session))
            ->assertRedirect();
        $this->session->refresh();
        $this->assertSame('ended', $this->session->status);
    }

    public function test_projector_leaderboard_route_is_reachable(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.live.leaderboard', $this->session))
            ->assertOk();
    }

    public function test_suspended_user_blocked_from_profile(): void
    {
        $this->student->update(['is_active' => false]);

        $this->actingAs($this->student)
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }
}
