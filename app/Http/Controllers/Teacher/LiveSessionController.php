<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveSessionController extends Controller
{
    public function start(
        Request $request,
        Quiz $quiz,
        NotificationService $notifications,
        ActivityLogService $activity
    ): RedirectResponse {
        if ($quiz->teacher_id !== $request->user()->id) {
            abort(403);
        }

        if (! $quiz->is_published) {
            // One-click: going live publishes the whole quiz (every question included).
            $quiz->update(['is_published' => true]);
        }

        $firstQuestion = $quiz->questions()->orderBy('sort_order')->orderBy('id')->first();
        if (! $firstQuestion) {
            return redirect()
                ->route('teacher.quizzes.show', $quiz)
                ->with('status', 'Add at least one question before going live.');
        }

        // Never leave orphan "LIVE" sessions open — close this teacher's
        // unfinished rooms before starting a fresh one.
        QuizSession::query()
            ->where('teacher_id', $request->user()->id)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'reveal_answer' => true,
            ]);

        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'classroom_id' => $quiz->classroom_id,
            'teacher_id' => $request->user()->id,
            'status' => 'active',
            'current_question_id' => $firstQuestion->id,
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'started_at' => now(),
        ]);

        $notifications->sessionStarted($session);
        $activity->log($request->user(), 'session.started', "Started session for {$quiz->title}", $request);

        return redirect()->route('teacher.live.show', $session);
    }

    public function show(Request $request, QuizSession $session): View
    {
        $this->authorizeTeacher($request, $session);

        $session->load(['quiz.questions.options', 'classroom', 'currentQuestion.options']);

        return view('teacher.live.show', compact('session'));
    }

    public function pause(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);
        abort_unless($session->status === 'active', 422);

        $session->update(['status' => 'paused']);

        return back()->with('status', 'Session paused.');
    }

    public function resume(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);
        abort_unless($session->status === 'paused', 422);

        $session->update([
            'status' => 'active',
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
        ]);

        return back()->with('status', 'Session resumed.');
    }

    public function extend(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);
        abort_unless(in_array($session->status, ['active', 'paused'], true), 422);

        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        $session->increment('time_bonus_seconds', (int) $data['seconds']);

        return back()->with('status', "Timer extended by {$data['seconds']} seconds for this question.");
    }

    public function setTime(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);
        abort_unless(in_array($session->status, ['active', 'paused'], true), 422);

        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        $question = $session->currentQuestion;
        if (! $question) {
            return back()->with('status', 'No active question to set time for.');
        }

        $base = max(5, (int) $question->time_limit_seconds);
        $desired = (int) $data['seconds'];
        // Bonus may be negative so teachers can shorten below the stored base (floor is 5s total).
        $session->update([
            'time_bonus_seconds' => $desired - $base,
        ]);

        if ($request->boolean('save_to_question')) {
            $question->update(['time_limit_seconds' => $desired]);
            $session->update(['time_bonus_seconds' => 0]);
        }

        return back()->with('status', "Current question timer set to {$desired} seconds.");
    }

    public function leaderboard(Request $request, QuizSession $session): View
    {
        $this->authorizeTeacher($request, $session);

        $session->load(['quiz', 'classroom', 'currentQuestion']);

        return view('teacher.live.leaderboard', compact('session'));
    }

    public function next(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);
        abort_unless(in_array($session->status, ['active', 'paused'], true), 422);

        $questions = $session->quiz
            ->questions()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($questions->isEmpty()) {
            return $this->end($request, $session, app(NotificationService::class), app(ActivityLogService::class));
        }

        $currentId = (int) $session->current_question_id;
        $currentIndex = $questions->search(fn ($id) => $id === $currentId);

        // If the current question was deleted or IDs drifted, resume from the first remaining question.
        if ($currentIndex === false) {
            $session->update([
                'current_question_id' => $questions->first(),
                'question_started_at' => now(),
                'time_bonus_seconds' => 0,
                'reveal_answer' => false,
                'status' => 'active',
            ]);

            return back()->with('status', 'Resumed on the first available question.');
        }

        if ($currentIndex >= $questions->count() - 1) {
            return $this->end($request, $session, app(NotificationService::class), app(ActivityLogService::class));
        }

        $session->update([
            'current_question_id' => $questions[$currentIndex + 1],
            'question_started_at' => now(),
            'time_bonus_seconds' => 0,
            'reveal_answer' => false,
            'status' => 'active',
        ]);

        $number = $currentIndex + 2;
        $total = $questions->count();

        return back()->with('status', "Moved to question {$number} of {$total}.");
    }

    public function skip(Request $request, QuizSession $session): RedirectResponse
    {
        return $this->next($request, $session);
    }

    public function reveal(Request $request, QuizSession $session): RedirectResponse
    {
        $this->authorizeTeacher($request, $session);

        $session->update(['reveal_answer' => true]);

        return back()->with('status', 'Answer revealed.');
    }

    public function end(
        Request $request,
        QuizSession $session,
        NotificationService $notifications,
        ActivityLogService $activity
    ): RedirectResponse {
        $this->authorizeTeacher($request, $session);

        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
            'reveal_answer' => true,
        ]);

        $notifications->sessionEnded($session);
        $activity->log($request->user(), 'session.ended', "Ended session #{$session->id}", $request);

        return redirect()
            ->route('teacher.analytics.show', $session)
            ->with('status', 'Session ended.');
    }

    private function authorizeTeacher(Request $request, QuizSession $session): void
    {
        abort_unless($session->teacher_id === $request->user()->id, 403);
    }
}
