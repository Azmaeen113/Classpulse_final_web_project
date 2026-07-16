<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end live session smoke covering teacher + student paths used by the checklist.
 */
class LiveSessionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_live_session_scoring_analytics_and_csv(): void
    {
        $teacher = User::factory()->teacher()->create();
        $s1 = User::factory()->create(['name' => 'Alice Fast']);
        $s2 = User::factory()->create(['name' => 'Bob Slow']);

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'name' => 'Flow Class',
            'room_code' => 'FLOW01',
            'is_active' => true,
        ]);
        $classroom->students()->attach([$s1->id, $s2->id], ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Flow Quiz',
            'is_published' => true,
            'default_time_limit_seconds' => 30,
        ]);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'mcq',
            'prompt' => '2+2?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 0,
            'short_answer_match' => 'exact',
        ]);
        foreach (['3', '4', '5', '6'] as $i => $text) {
            QuestionOption::create([
                'question_id' => $q1->id,
                'option_text' => $text,
                'is_correct' => $text === '4',
                'sort_order' => $i,
            ]);
        }

        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'type' => 'short_answer',
            'prompt' => 'Capital of France?',
            'points' => 100,
            'time_limit_seconds' => 30,
            'sort_order' => 1,
            'short_answer_expected' => 'Paris',
            'short_answer_match' => 'exact',
        ]);

        // Start live
        $this->actingAs($teacher)
            ->post(route('teacher.live.start', $quiz))
            ->assertRedirect();

        $session = QuizSession::first();
        $this->assertNotNull($session);
        $this->assertSame('active', $session->status);

        // Both students enter the live room before anyone answers.
        $this->actingAs($s1)->get(route('student.live.show', $session))->assertOk();
        $this->actingAs($s2)->get(route('student.live.show', $session))->assertOk();

        $correctOpt = $q1->options()->where('is_correct', true)->value('id');

        // Student 1 answers correctly quickly
        $this->actingAs($s1)->postJson(route('student.live.answer', $session), [
            'question_id' => $q1->id,
            'selected_option_id' => $correctOpt,
        ])->assertOk();

        // Student 2 wrong
        $wrongOpt = $q1->options()->where('is_correct', false)->value('id');
        $this->actingAs($s2)->postJson(route('student.live.answer', $session), [
            'question_id' => $q1->id,
            'selected_option_id' => $wrongOpt,
        ])->assertOk();

        $this->assertSame(2, SessionResponse::query()
            ->where('quiz_session_id', $session->id)
            ->where('question_id', $q1->id)
            ->count());

        // After everyone present answers, session auto-advances to Q2.
        $session->refresh();
        $this->assertSame($q2->id, (int) $session->current_question_id);

        $this->actingAs($teacher)
            ->getJson(route('api.poll.answer-distribution', $session))
            ->assertOk();

        // Answer Q2 using different capitalization — both should be accepted.
        $this->actingAs($s1)->postJson(route('student.live.answer', $session), [
            'question_id' => $q2->id,
            'short_answer_text' => 'Paris',
        ])->assertOk();
        $this->actingAs($s2)->postJson(route('student.live.answer', $session), [
            'question_id' => $q2->id,
            'short_answer_text' => 'paris',
        ])->assertOk();

        $this->assertTrue(
            (bool) SessionResponse::where('student_id', $s1->id)->where('question_id', $q2->id)->value('is_correct')
        );
        $this->assertTrue(
            (bool) SessionResponse::where('student_id', $s2->id)->where('question_id', $q2->id)->value('is_correct')
        );

        // End
        $this->actingAs($teacher)->post(route('teacher.live.end', $session))->assertRedirect();
        $session->refresh();
        $this->assertSame('ended', $session->status);

        $board = app(LeaderboardService::class)->forSession($session);
        $this->assertSame('Alice Fast', $board->first()['name']);
        $this->assertGreaterThan($board->last()['total_points'], $board->first()['total_points']);

        // Analytics page + CSV
        $this->actingAs($teacher)
            ->get(route('teacher.analytics.show', $session))
            ->assertOk()
            ->assertSee('2+2?', false)
            ->assertSee('Alice Fast', false);

        $csv = $this->actingAs($teacher)
            ->get(route('teacher.analytics.export', $session));
        $csv->assertOk();
        $this->assertStringContainsString('Alice Fast', $csv->streamedContent());

        // Student results
        $this->actingAs($s1)
            ->get(route('student.sessions.result', $session))
            ->assertOk()
            ->assertSee('Alice Fast', false);
    }
}
