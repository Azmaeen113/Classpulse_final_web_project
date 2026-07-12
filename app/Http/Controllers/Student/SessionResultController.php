<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Services\LeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionResultController extends Controller
{
    public function show(Request $request, QuizSession $session, LeaderboardService $leaderboard): View|RedirectResponse
    {
        if ($session->status !== 'ended') {
            if (in_array($session->status, ['pending', 'active', 'paused'], true)) {
                return redirect()
                    ->route('student.live.show', $session)
                    ->with('status', 'This quiz is still live — jump back in.');
            }

            return redirect()
                ->route('student.history')
                ->with('status', 'Results appear after the teacher ends the quiz.');
        }

        $studentId = $request->user()->id;

        $inClass = $session->classroom
            ->students()
            ->where('student_id', $studentId)
            ->exists();

        abort_unless($inClass, 403);

        $participated = SessionResponse::query()
            ->where('quiz_session_id', $session->id)
            ->where('student_id', $studentId)
            ->exists();

        if (! $participated) {
            return redirect()
                ->route('student.history')
                ->with('status', 'You have no results for that session.');
        }

        $session->load(['quiz.questions.options', 'classroom']);

        $responses = SessionResponse::query()
            ->where('quiz_session_id', $session->id)
            ->where('student_id', $studentId)
            ->with(['question.options', 'selectedOption'])
            ->get()
            ->keyBy('question_id');

        $board = $leaderboard->forSession($session, 500);
        $mine = $board->firstWhere('student_id', $studentId);

        $result = [
            'score' => (int) ($mine['total_points'] ?? 0),
            'rank' => $mine['rank'] ?? null,
            'correct' => (int) ($mine['correct_count'] ?? 0),
            'total' => $session->quiz->questions->count(),
        ];

        $leaderboard = $board;

        return view('student.sessions.result', compact('session', 'responses', 'result', 'leaderboard'));
    }
}
