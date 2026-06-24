<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Services\ActivityLogService;
use App\Services\QrCodeService;
use App\Services\RoomCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $classrooms = $request->user()
            ->classroomsTaught()
            ->withCount('students')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%")
                        ->orWhere('room_code', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('teacher.classrooms.index', compact('classrooms', 'q'));
    }

    public function create(): View
    {
        return view('teacher.classrooms.create');
    }

    public function store(
        StoreClassroomRequest $request,
        RoomCodeService $roomCodes,
        QrCodeService $qr,
        ActivityLogService $activity
    ): RedirectResponse {
        $code = $roomCodes->generate();
        $joinUrl = $qr->joinUrl($code);

        $classroom = Classroom::create([
            'teacher_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'subject' => $request->validated('subject'),
            'room_code' => $code,
            'qr_payload' => $joinUrl,
            'is_active' => true,
        ]);

        $activity->log($request->user(), 'classroom.created', "Created classroom {$classroom->name}", $request);

        return redirect()
            ->route('teacher.classrooms.show', $classroom)
            ->with('status', 'Classroom created.');
    }

    public function show(Request $request, Classroom $classroom): View
    {
        $this->authorizeTeacher($request, $classroom);

        $classroom->load(['students', 'quizzes']);
        $qrSvg = app(QrCodeService::class)->svg($classroom->qr_payload ?: app(QrCodeService::class)->joinUrl($classroom->room_code));

        return view('teacher.classrooms.show', compact('classroom', 'qrSvg'));
    }

    public function edit(Request $request, Classroom $classroom): View
    {
        $this->authorizeTeacher($request, $classroom);

        return view('teacher.classrooms.edit', compact('classroom'));
    }

    public function update(
        UpdateClassroomRequest $request,
        Classroom $classroom,
        ActivityLogService $activity
    ): RedirectResponse {
        $this->authorizeTeacher($request, $classroom);

        $classroom->update($request->validated());

        $activity->log($request->user(), 'classroom.updated', "Updated classroom {$classroom->name}", $request);

        return redirect()
            ->route('teacher.classrooms.show', $classroom)
            ->with('status', 'Classroom updated.');
    }

    public function destroy(Request $request, Classroom $classroom, ActivityLogService $activity): RedirectResponse
    {
        $this->authorizeTeacher($request, $classroom);

        $name = $classroom->name;
        $classroom->delete();

        $activity->log($request->user(), 'classroom.deleted', "Deleted classroom {$name}", $request);

        return redirect()
            ->route('teacher.classrooms.index')
            ->with('status', 'Classroom deleted.');
    }

    private function authorizeTeacher(Request $request, Classroom $classroom): void
    {
        abort_unless($classroom->teacher_id === $request->user()->id, 403);
    }
}
