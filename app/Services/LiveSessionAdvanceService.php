<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Models\SessionResponse;
use Illuminate\Support\Facades\DB;

/**
 * Advances a live session to the next question when the timer expires
 * or when every *active* participant has answered the current question.
 */
class LiveSessionAdvanceService
{
    public function __construct(
        private LiveParticipationService $participants
    ) {}

    public function advanceIfNeeded(QuizSession $session): QuizSession
    {
        if ($session->status !== 'active') {
            return $session;
        }

        if (! $session->current_question_id || ! $session->question_started_at) {
            return $session;
        }

        return DB::transaction(function () use ($session) {
            /** @var QuizSession $locked */
            $locked = QuizSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'active') {
                return $session->refresh();
            }

            $locked->loadMissing(['currentQuestion', 'quiz', 'classroom']);

            if ($this->shouldAdvance($locked)) {
                $this->moveNextOrEnd($locked);
            }

            return $locked->refresh();
        });
    }

    private function shouldAdvance(QuizSession $session): bool
    {
        $question = $session->currentQuestion;
        if (! $question || ! $session->question_started_at) {
            return false;
        }

        $limitSeconds = $session->effectiveTimeLimitSeconds($question);
        $deadline = $session->question_started_at->copy()->addSeconds($limitSeconds + 1);

        if (now()->greaterThanOrEqualTo($deadline)) {
            return true;
        }

        $activeIds = $this->participants->activeStudentIds($session);
        $activeCount = count($activeIds);

        // Nobody in the live room yet — don't auto-skip.
        if ($activeCount < 1) {
            return false;
        }

        $answered = SessionResponse::query()
            ->where('quiz_session_id', $session->id)
            ->where('question_id', $question->id)
            ->whereIn('student_id', $activeIds)
            ->count();

        if ($answered < $activeCount) {
            return false;
        }

        // If only some of the class joined live, still advance when every
        // present player has answered (absent students are skipped).
        return true;
    }

    private function moveNextOrEnd(QuizSession $session): void
    {
        $questions = $session->quiz
            ->questions()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($questions->isEmpty()) {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
                'reveal_answer' => true,
            ]);

            return;
        }

        $currentId = (int) $session->current_question_id;
        $currentIndex = $questions->search(fn ($id) => $id === $currentId);

        if ($currentIndex === false) {
            $session->update([
                'current_question_id' => $questions->first(),
                'question_started_at' => now(),
                'time_bonus_seconds' => 0,
                'reveal_answer' => false,
                'status' => 'active',
            ]);

            return;
        }

        if ($currentIndex >= $questions->count() - 1) {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
                'reveal_answer' => true,
            ]);

            return;
        }

        $session->update([
            'current_question_id' => $questions[$currentIndex + 1],
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'status' => 'active',
        ]);
    }
}
