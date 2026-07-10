<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
            'classrooms' => Classroom::count(),
            'quizzes' => Quiz::count(),
            'active_sessions' => QuizSession::whereIn('status', ['active', 'paused'])->count(),
            'recent_logs' => ActivityLog::with('user')->latest()->limit(10)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
