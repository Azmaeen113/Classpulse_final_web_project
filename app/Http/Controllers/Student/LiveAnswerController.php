<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Services\LiveParticipationService;
use App\Services\LiveSessionAdvanceService;
use App\Services\ScoringService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveAnswerController extends Controller
{
    public function show(
        Request $request,
        QuizSession $session,
        LiveSessionAdvanceService $advance,
        LiveParticipationService $participants
    ): View {
        $this->authorizeStudent($request, $session);

        $participants->touch($session, (int) $request->user()->id);
        $session = $advance->advanceIfNeeded($session);
        $session->load(['quiz', 'classroom', 'currentQuestion.options']);

        return view('student.live.show', compact('session'));
    }

    public function submit(
        SubmitAnswerRequest $request,
        QuizSession $session,
        ScoringService $scoring,
        LiveSessionAdvanceService $advance,
        LiveParticipationService $participants
    ): JsonResponse {
        $this->authorizeStudent($request, $session);

        $participants->touch($session, (int) $request->user()->id);

        // Do not auto-advance before scoring this submission — a late answer must
        // still attach to the question the student was on.
        if ($session->status !== 'active') {
            return response()->json([
                'ok' => false,
                'message' => $session->status === 'paused'
                    ? 'Session is paused. Answers are not accepted right now.'
                    : 'Session is not accepting answers.',
            ], 422);
        }

        $data = $request->validated();
        $question = $session->currentQuestion;

        if (! $question || (int) $data['question_id'] !== (int) $question->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Question mismatch.',
            ], 422);
        }

        $existing = SessionResponse::query()
            ->where('quiz_session_id', $session->id)
            ->where('question_id', $question->id)
            ->where('student_id', $request->user()->id)
            ->first();

        if ($existing) {
            return $this->existingAnswerResponse($existing, $session);
        }

        $effectiveSeconds = $session->effectiveTimeLimitSeconds($question);
        $responseTimeMs = $this->serverElapsedMs($session);
        $limitMs = max(1, $effectiveSeconds) * 1000;
        $isLate = $session->question_started_at !== null && $responseTimeMs > $limitMs;
        $isAuto = (bool) ($data['is_auto_submit'] ?? false) || $isLate;

        if ($isLate) {
            $responseTimeMs = $limitMs;
        }

        $result = $scoring->score(
            $question,
            $responseTimeMs,
            $isLate ? null : ($data['selected_option_id'] ?? null),
            $isLate ? null : ($data['short_answer_text'] ?? null),
            $effectiveSeconds
        );

        // Late answers after the server deadline score zero.
        if ($isLate) {
            $result = [
                'is_correct' => false,
                'points_awarded' => 0,
            ];
        }

        try {
            $response = SessionResponse::create([
                'quiz_session_id' => $session->id,
                'question_id' => $question->id,
                'student_id' => $request->user()->id,
                'selected_option_id' => $isLate ? null : ($data['selected_option_id'] ?? null),
                'short_answer_text' => $isLate ? null : ($data['short_answer_text'] ?? null),
                'is_correct' => $result['is_correct'],
                'points_awarded' => $result['points_awarded'],
                'response_time_ms' => $responseTimeMs,
                'is_auto_submit' => $isAuto,
                'answered_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Unique (session, question, student) race — return the existing row cleanly.
            $existing = SessionResponse::query()
                ->where('quiz_session_id', $session->id)
                ->where('question_id', $question->id)
                ->where('student_id', $request->user()->id)
                ->first();

            if ($existing) {
                return $this->existingAnswerResponse($existing, $session, $advance);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Could not save your answer. Please try again.',
            ], 422);
        }

        return $this->answerPayload($response, $session, false, $advance);
    }

    private function serverElapsedMs(QuizSession $session): int
    {
        if (! $session->question_started_at) {
            return 0;
        }

        // Always compute forward elapsed time (never trust signed Carbon diffs).
        $elapsed = now()->getTimestampMs() - $session->question_started_at->getTimestampMs();

        return (int) max(0, $elapsed);
    }

    private function existingAnswerResponse(
        SessionResponse $existing,
        QuizSession $session,
        ?LiveSessionAdvanceService $advance = null
    ): JsonResponse {
        return $this->answerPayload($existing, $session, true, $advance);
    }

    private function answerPayload(
        SessionResponse $response,
        QuizSession $session,
        bool $already,
        ?LiveSessionAdvanceService $advance = null
    ): JsonResponse {
        $previousQuestionId = (int) $session->current_question_id;

        if ($advance) {
            $session = $advance->advanceIfNeeded($session);
        }

        $revealed = (bool) $session->reveal_answer;
        $advanced = $session->status === 'ended'
            || (int) $session->current_question_id !== $previousQuestionId;

        return response()->json([
            'ok' => true,
            'already_answered' => $already,
            'message' => $already ? 'Already answered.' : null,
            // Hide correctness until teacher reveals (Socrative pacing).
            'is_correct' => $revealed ? (bool) $response->is_correct : null,
            'points_awarded' => $revealed ? (int) $response->points_awarded : null,
            'reveal_answer' => $revealed,
            'advanced' => $advanced,
            'session_status' => $session->status,
            'current_question_id' => $session->current_question_id,
        ]);
    }

    private function authorizeStudent(Request $request, QuizSession $session): void
    {
        abort_unless($request->user()?->is_active, 403, 'Account inactive.');

        $inClass = $session->classroom
            ->students()
            ->where('student_id', $request->user()->id)
            ->exists();

        abort_unless($inClass, 403);
    }
}
