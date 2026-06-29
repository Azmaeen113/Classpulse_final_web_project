<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $classrooms = $student->classroomsJoined()->with('teacher')->get();
        $classroomIds = $classrooms->pluck('id');

        $activeSessions = QuizSession::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->with(['quiz', 'classroom'])
            ->latest()
            ->get();

        return view('student.dashboard', compact('classrooms', 'activeSessions'));
    }
}
