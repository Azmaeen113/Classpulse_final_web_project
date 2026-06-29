<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        $classrooms = $teacher->classroomsTaught()->withCount('students')->latest()->get();
        $quizzes = $teacher->quizzes()->with('classroom')->latest()->limit(10)->get();
        $activeSessions = QuizSession::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->with(['quiz', 'classroom'])
            ->latest()
            ->get();

        return view('teacher.dashboard', compact('classrooms', 'quizzes', 'activeSessions'));
    }
}
