<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\SessionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = $request->user()->id;
        $q = trim((string) $request->query('q', ''));

        $participatedIds = SessionResponse::query()
            ->where('student_id', $studentId)
            ->distinct()
            ->pluck('quiz_session_id');

        $sessions = QuizSession::query()
            ->whereIn('id', $participatedIds)
            ->where('status', 'ended')
            ->with(['quiz', 'classroom'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('quiz', fn ($quiz) => $quiz->where('title', 'like', "%{$q}%"))
                        ->orWhereHas('classroom', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $scores = SessionResponse::query()
            ->select('quiz_session_id', DB::raw('SUM(points_awarded) as total_points'))
            ->where('student_id', $studentId)
            ->whereIn('quiz_session_id', $sessions->pluck('id'))
            ->groupBy('quiz_session_id')
            ->pluck('total_points', 'quiz_session_id');

        return view('student.history.index', compact('sessions', 'scores', 'q'));
    }
}
