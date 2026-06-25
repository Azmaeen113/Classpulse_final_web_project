<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinClassroomRequest;
use App\Models\Classroom;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JoinClassroomController extends Controller
{
    public function show(Request $request): View
    {
        $code = strtoupper((string) ($request->query('code') ?: $request->cookie('last_room_code', '')));

        return view('student.join', compact('code'));
    }

    public function join(
        JoinClassroomRequest $request,
        NotificationService $notifications,
        ActivityLogService $activity
    ): RedirectResponse {
        $code = $request->validated('room_code');

        $classroom = Classroom::query()
            ->where('room_code', $code)
            ->where('is_active', true)
            ->first();

        if (! $classroom) {
            return back()->withErrors(['room_code' => 'Invalid or inactive room code.'])->withInput();
        }

        $student = $request->user();
        $already = $classroom->students()->where('student_id', $student->id)->exists();

        if (! $already) {
            $classroom->students()->attach($student->id, ['joined_at' => now()]);
            $notifications->studentJoined($classroom, $student);
            $activity->log($student, 'classroom.joined', "Joined {$classroom->name}", $request);
        }

        return redirect()
            ->route('student.dashboard')
            ->with('status', $already ? 'Already in this classroom.' : 'Joined classroom successfully.')
            ->withCookie(cookie('last_room_code', $code, 60 * 24 * 30));
    }
}
