<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $quizzes = $request->user()
            ->quizzes()
            ->with('classroom')
            ->withCount('questions')
            ->latest()
            ->paginate(12);

        return view('teacher.quizzes.index', compact('quizzes'));
    }

    public function create(Request $request): View
    {
        $classrooms = $request->user()->classroomsTaught()->orderBy('name')->get();

        return view('teacher.quizzes.create', compact('classrooms'));
    }

    public function store(StoreQuizRequest $request, ActivityLogService $activity): RedirectResponse
    {
        $data = $request->validated();

        $quiz = Quiz::create([
            ...$data,
            'teacher_id' => $request->user()->id,
            'is_published' => (bool) $request->boolean('is_published'),
            'default_time_limit_seconds' => (int) ($data['default_time_limit_seconds'] ?? 30),
        ]);

        $activity->log($request->user(), 'quiz.created', "Created quiz {$quiz->title}", $request);

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'Quiz created.');
    }

    public function show(Request $request, Quiz $quiz): View
    {
        $this->authorizeTeacher($request, $quiz);

        $quiz->load([
            'classroom',
            'questions' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'questions.options' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('teacher.quizzes.show', compact('quiz'));
    }

    public function edit(Request $request, Quiz $quiz): View
    {
        $this->authorizeTeacher($request, $quiz);

        $classrooms = $request->user()->classroomsTaught()->orderBy('name')->get();

        return view('teacher.quizzes.edit', compact('quiz', 'classrooms'));
    }

    public function update(Request $request, Quiz $quiz, ActivityLogService $activity): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'default_time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
        ]);

        abort_unless(
            Classroom::where('id', $data['classroom_id'])->where('teacher_id', $request->user()->id)->exists(),
            403
        );

        $quiz->update([
            ...$data,
            'is_published' => (bool) $request->boolean('is_published'),
            'default_time_limit_seconds' => (int) ($data['default_time_limit_seconds'] ?? ($quiz->default_time_limit_seconds ?: 30)),
        ]);

        $activity->log($request->user(), 'quiz.updated', "Updated quiz {$quiz->title}", $request);

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'Quiz updated.');
    }

    public function destroy(Request $request, Quiz $quiz, ActivityLogService $activity): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $title = $quiz->title;
        $quiz->delete();

        $activity->log($request->user(), 'quiz.deleted', "Deleted quiz {$title}", $request);

        return redirect()
            ->route('teacher.quizzes.index')
            ->with('status', 'Quiz deleted.');
    }

    /**
     * Publish (or unpublish) the entire quiz — every question goes live-ready together.
     */
    public function publish(Request $request, Quiz $quiz, ActivityLogService $activity): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $publish = ! $request->boolean('unpublish');
        $count = $quiz->questions()->count();

        if ($publish && $count < 1) {
            return back()->with('status', 'Add at least one question before publishing.');
        }

        $quiz->update(['is_published' => $publish]);

        $activity->log(
            $request->user(),
            $publish ? 'quiz.published' : 'quiz.unpublished',
            ($publish ? 'Published' : 'Unpublished')." quiz {$quiz->title} ({$count} questions)",
            $request
        );

        return back()->with(
            'status',
            $publish
                ? "Quiz published — all {$count} question(s) are ready for a live session."
                : 'Quiz moved back to draft.'
        );
    }

    private function authorizeTeacher(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->teacher_id === $request->user()->id, 403);
    }
}
