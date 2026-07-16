<?php

namespace Tests\Feature;

use App\Http\Middleware\ConfigureZoneSession;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiZoneSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_cookie_names_are_role_specific(): void
    {
        $this->assertSame('cp_session_teacher', ConfigureZoneSession::cookieName('teacher'));
        $this->assertSame('cp_session_student', ConfigureZoneSession::cookieName('student'));
        $this->assertSame('cp_session_admin', ConfigureZoneSession::cookieName('admin'));
        $this->assertSame('cp_session_shared', ConfigureZoneSession::cookieName('shared'));
    }

    public function test_zone_claim_logs_teacher_into_destination(): void
    {
        $teacher = User::factory()->teacher()->create();
        $token = Str::random(64);

        Cache::put('cp_zone_claim:'.$token, [
            'user_id' => $teacher->id,
            'role' => 'teacher',
            'remember' => false,
            'destination' => '/teacher/dashboard',
            'status' => 'ok',
        ], now()->addMinutes(3));

        $response = $this->get(route('zone.claim', ['zone' => 'teacher', 'token' => $token]));

        $response->assertRedirect('/teacher/dashboard');
        $this->assertAuthenticatedAs($teacher);
    }

    public function test_theme_toggle_from_teacher_zone_does_not_419(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->withHeader('X-ClassPulse-Zone', 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk();

        $response = $this->actingAs($teacher)
            ->withHeader('X-ClassPulse-Zone', 'teacher')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('preferences.theme'), ['theme' => 'light']);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('theme', 'light');
    }

    public function test_theme_toggle_from_student_zone_does_not_419(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->withHeader('X-ClassPulse-Zone', 'student')
            ->get(route('student.dashboard'))
            ->assertOk();

        $response = $this->actingAs($student)
            ->withHeader('X-ClassPulse-Zone', 'student')
            ->postJson(route('preferences.theme'), ['theme' => 'dark']);

        $response->assertOk()->assertJsonPath('theme', 'dark');
    }

    public function test_reset_browser_route_clears_zone_cookies(): void
    {
        $response = $this->get(route('auth.reset-browser'));

        $response->assertRedirect(route('login'));
    }

    public function test_dual_role_live_poll_and_answer_contract(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Zone Class',
            'room_code' => 'ZONE01',
            'is_active' => true,
        ]);
        $classroom->students()->attach($student->id, ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Zone Quiz',
            'is_published' => true,
            'default_time_limit_seconds' => 30,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'Water is wet.',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_match' => 'exact',
        ]);
        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 0,
        ]);
        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 1,
        ]);

        // Second question keeps the session active after sole-participant auto-advance.
        $question2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'prompt' => 'Ice is cold.',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 1,
            'short_answer_match' => 'exact',
        ]);
        QuestionOption::create([
            'question_id' => $question2->id,
            'option_text' => 'True',
            'is_correct' => true,
            'sort_order' => 0,
        ]);
        QuestionOption::create([
            'question_id' => $question2->id,
            'option_text' => 'False',
            'is_correct' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($teacher)->post(route('teacher.live.start', $quiz))->assertRedirect();
        $session = QuizSession::firstOrFail();

        // Student sees live question via poll contract
        $state = $this->actingAs($student)
            ->withHeader('X-ClassPulse-Zone', 'student')
            ->getJson(route('api.poll.session-state', $session));

        $state->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('current_question_id', $question->id)
            ->assertJsonPath('answered', false);

        $optionId = $question->options()->where('is_correct', true)->value('id');

        $this->actingAs($student)
            ->withHeader('X-ClassPulse-Zone', 'student')
            ->postJson(route('student.live.answer', $session), [
                'question_id' => $question->id,
                'selected_option_id' => $optionId,
            ])
            ->assertOk()
            ->assertJsonPath('already_answered', false)
            ->assertJsonPath('advanced', true)
            ->assertJsonPath('is_correct', null);

        $session->refresh();
        $this->assertSame($question2->id, (int) $session->current_question_id);

        // Teacher sees counter for the new current question (0 answers yet).
        $this->actingAs($teacher)
            ->withHeader('X-ClassPulse-Zone', 'teacher')
            ->getJson(route('api.poll.response-counter', $session))
            ->assertOk()
            ->assertJsonPath('answered', 0);

        // Pause blocks student on Q2
        $this->actingAs($teacher)->post(route('teacher.live.pause', $session))->assertRedirect();
        $option2Id = $question2->options()->where('is_correct', true)->value('id');
        $this->actingAs($student)
            ->postJson(route('student.live.answer', $session), [
                'question_id' => $question2->id,
                'selected_option_id' => $option2Id,
            ])
            ->assertStatus(422);

        // Reveal then student poll shows correctness flags on options
        $this->actingAs($teacher)->post(route('teacher.live.resume', $session));
        $this->actingAs($teacher)->post(route('teacher.live.reveal', $session));

        $revealed = $this->actingAs($student)
            ->getJson(route('api.poll.session-state', $session));

        $revealed->assertOk()->assertJsonPath('reveal_answer', true);
        $options = $revealed->json('current_question.options');
        $this->assertNotNull(collect($options)->firstWhere('is_correct', true));
    }
}
