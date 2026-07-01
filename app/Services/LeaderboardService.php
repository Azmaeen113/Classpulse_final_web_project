<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Models\SessionResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Aggregate leaderboard from session_responses.
     * Rank by SUM(points_awarded) DESC, then AVG(response_time_ms) ASC as tiebreaker.
     */
    public function forSession(QuizSession|int $session, int $limit = 50): Collection
    {
        $sessionId = $session instanceof QuizSession ? $session->id : $session;

        return SessionResponse::query()
            ->select([
                'student_id',
                DB::raw('SUM(points_awarded) as total_points'),
                DB::raw('AVG(response_time_ms) as avg_response_time_ms'),
                DB::raw('COUNT(*) as answers_count'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
            ])
            ->where('quiz_session_id', $sessionId)
            ->groupBy('student_id')
            ->orderByDesc('total_points')
            ->orderBy('avg_response_time_ms')
            ->limit($limit)
            ->with('student:id,name,avatar')
            ->get()
            ->values()
            ->map(function ($row, int $index) {
                return [
                    'rank' => $index + 1,
                    'student_id' => $row->student_id,
                    'name' => $row->student?->name,
                    'avatar' => $row->student?->avatar,
                    'total_points' => (int) $row->total_points,
                    'avg_response_time_ms' => (int) round((float) $row->avg_response_time_ms),
                    'answers_count' => (int) $row->answers_count,
                    'correct_count' => (int) $row->correct_count,
                ];
            });
    }
}
