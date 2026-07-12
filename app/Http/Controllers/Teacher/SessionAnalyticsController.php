<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SessionAnalyticsController extends Controller
{
    public function show(Request $request, QuizSession $session, LeaderboardService $leaderboard): View
    {
        abort_unless($session->teacher_id === $request->user()->id, 403);

        $session->load(['quiz.questions', 'classroom']);

        $statsByQuestion = SessionResponse::query()
            ->select([
                'question_id',
                DB::raw('COUNT(*) as total_answers'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers'),
                DB::raw('AVG(response_time_ms) as avg_ms'),
            ])
            ->where('quiz_session_id', $session->id)
            ->groupBy('question_id')
            ->get()
            ->keyBy('question_id');

        // Normalize keys the Blade + Chart.js expect.
        $perQuestion = $session->quiz->questions
            ->sortBy('sort_order')
            ->values()
            ->map(function ($question) use ($statsByQuestion) {
                $stats = $statsByQuestion->get($question->id);
                $total = (int) ($stats->total_answers ?? 0);
                $correct = (int) ($stats->correct_answers ?? 0);
                $accuracy = $total > 0 ? round(($correct / $total) * 100, 1) : 0.0;

                return [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'question' => $question->prompt,
                    'total' => $total,
                    'total_answers' => $total,
                    'correct' => $correct,
                    'correct_answers' => $correct,
                    'avg_ms' => (int) round((float) ($stats->avg_ms ?? 0)),
                    'accuracy' => $accuracy,
                    'percent' => $accuracy,
                ];
            });

        $rows = $leaderboard->forSession($session);

        return view('teacher.analytics.show', compact('session', 'perQuestion', 'rows'));
    }

    public function exportCsv(Request $request, QuizSession $session, LeaderboardService $leaderboard): StreamedResponse
    {
        abort_unless($session->teacher_id === $request->user()->id, 403);

        $rows = $leaderboard->forSession($session, 1000);
        $filename = 'session-'.$session->id.'-leaderboard.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['rank', 'student_id', 'name', 'total_points', 'avg_response_time_ms', 'answers_count', 'correct_count']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['rank'],
                    $row['student_id'],
                    $row['name'],
                    $row['total_points'],
                    $row['avg_response_time_ms'],
                    $row['answers_count'],
                    $row['correct_count'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
