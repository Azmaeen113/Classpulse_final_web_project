<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = QuizSession::query()
            ->where('teacher_id', $request->user()->id)
            ->with(['quiz', 'classroom'])
            ->latest()
            ->paginate(15);

        return view('teacher.history.index', compact('sessions'));
    }
}
