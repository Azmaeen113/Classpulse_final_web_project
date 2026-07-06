<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionOption;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Services\LeaderboardService;
use App\Services\LiveParticipationService;
use App\Services\LiveSessionAdvanceService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function activeSessionsForStudent(Request $request): JsonResponse
    {
        $classroomIds = $request->user()->classroomsJoined()->pluck('classrooms.id');

        $sessions = QuizSession::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->with(['quiz:id,title', 'classroom:id,name,room_code'])
            ->latest()
            ->get(['id', 'quiz_id', 'classroom_id', 'status', 'current_question_id', 'started_at']);

        return response()->json(['sessions' => $sessions]);
    }

    public function sessionState(
        Request $request,
        QuizSession $session,
        LiveSessionAdvanceService $advance,
        LiveParticipationService $participants
    ): JsonResponse {
        $this->authorizeSessionAccess($request, $session);

        if ($request->user()->isStudent()) {
            $participants->touch($session, (int) $request->user()->id);
        }

        $session = $advance->advanceIfNeeded($session);

        $session->load(['currentQuestion.options', 'quiz:id,title']);

        $question = $session->currentQuestion;
        $answered = false;

        if ($question && $request->user()->isStudent()) {
            $answered = SessionResponse::query()
                ->where('quiz_session_id', $session->id)
                ->where('question_id', $question->id)
                ->where('student_id', $request->user()->id)
                ->exists();
        }

        $options = [];
        if ($question) {
            $options = $question->options->sortBy('sort_order')->values()->map(fn ($o) => [
                'id' => $o->id,
                'option_text' => $o->option_text,
                'is_correct' => $session->reveal_answer ? $o->is_correct : null,
                'sort_order' => $o->sort_order,
            ]);
        }

        $timeLimit = $question ? $session->effectiveTimeLimitSeconds($question) : 0;

        $questionIds = $session->quiz
            ? $session->quiz->questions()->orderBy('sort_order')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values()
            : collect();
        $questionCount = $questionIds->count();
        $questionIndex = $question
            ? ($questionIds->search(fn ($id) => $id === (int) $question->id) )
            : false;
        $questionNumber = $questionIndex === false ? null : ((int) $questionIndex + 1);

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'reveal_answer' => $session->reveal_answer,
            'server_now' => now()->toIso8601String(),
            'question_started_at' => optional($session->question_started_at)?->toIso8601String(),
            'time_limit_seconds' => $timeLimit,
            'time_bonus_seconds' => (int) $session->time_bonus_seconds,
            'current_question_id' => $question?->id,
            'question_number' => $questionNumber,
            'question_count' => $questionCount,
            'current_question' => $question ? [
                'id' => $question->id,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'image_path' => $question->image_path,
                'points' => $question->points,
                'time_limit_seconds' => $timeLimit,
                'base_time_limit_seconds' => (int) $question->time_limit_seconds,
                'options' => $options,
            ] : null,
            'answered' => $answered,
            'already_answered' => $answered,
            'has_answered' => $answered,
            'quiz_title' => $session->quiz?->title,
        ]);
    }

    public function responseCounter(Request $request, QuizSession $session): JsonResponse
    {
        $this->authorizeTeacherOrMember($request, $session);

        $questionId = $session->current_question_id;
        $totalStudents = $session->classroom->students()->count();
        $answered = 0;

        if ($questionId) {
            $answered = SessionResponse::query()
                ->where('quiz_session_id', $session->id)
                ->where('question_id', $questionId)
                ->count();
        }

        return response()->json([
            'question_id' => $questionId,
            'answered' => $answered,
            'total' => $totalStudents,
        ]);
    }

    public function answerDistribution(Request $request, QuizSession $session): JsonResponse
    {
        $this->authorizeTeacherOrMember($request, $session);

        $questionId = $session->current_question_id;
        if (! $questionId) {
            return response()->json(['distribution' => []]);
        }

        $options = QuestionOption::where('question_id', $questionId)->orderBy('sort_order')->get();

        $counts = SessionResponse::query()
            ->select('selected_option_id', DB::raw('COUNT(*) as total'))
            ->where('quiz_session_id', $session->id)
            ->where('question_id', $questionId)
            ->whereNotNull('selected_option_id')
            ->groupBy('selected_option_id')
            ->pluck('total', 'selected_option_id');

        $distribution = $options->map(fn ($opt) => [
            'option_id' => $opt->id,
            'option_text' => $opt->option_text,
            'count' => (int) ($counts[$opt->id] ?? 0),
            'is_correct' => $session->reveal_answer ? $opt->is_correct : null,
        ]);

        return response()->json(['distribution' => $distribution]);
    }

    public function leaderboard(Request $request, QuizSession $session, LeaderboardService $leaderboard): JsonResponse
    {
        $this->authorizeTeacherOrMember($request, $session);

        return response()->json([
            'leaderboard' => $leaderboard->forSession($session),
        ]);
    }

    public function notificationsUnread(Request $request, NotificationService $notifications): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $notifications->unreadCount($user),
            'notifications' => $user->appNotifications()
                ->whereNull('read_at')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    private function authorizeSessionAccess(Request $request, QuizSession $session): void
    {
        $user = $request->user();

        if ($user->isTeacher() && $session->teacher_id === $user->id) {
            return;
        }

        if ($user->isStudent()) {
            $inClass = $session->classroom->students()->where('student_id', $user->id)->exists();
            abort_unless($inClass, 403);

            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        abort(403);
    }

    private function authorizeTeacherOrMember(Request $request, QuizSession $session): void
    {
        $this->authorizeSessionAccess($request, $session);
    }
}
