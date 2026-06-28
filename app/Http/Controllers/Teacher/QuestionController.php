<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function create(Request $request, Quiz $quiz): View
    {
        $this->authorizeTeacher($request, $quiz);

        $defaultTime = (int) ($quiz->default_time_limit_seconds ?: 30);

        return view('teacher.questions.create', compact('quiz', 'defaultTime'));
    }

    public function store(StoreQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $data = $request->validated();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $sortOrder = $data['sort_order'] ?? ((int) $quiz->questions()->max('sort_order') + 1);

        DB::transaction(function () use ($quiz, $data, $imagePath, $sortOrder) {
            $question = $quiz->questions()->create([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'image_path' => $imagePath,
                'points' => $data['points'] ?? 100,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? (int) ($quiz->default_time_limit_seconds ?: 30),
                'sort_order' => $sortOrder,
                'short_answer_expected' => $data['type'] === 'short_answer'
                    ? trim((string) ($data['short_answer_expected'] ?? ''))
                    : null,
                // Exact fill-in-the-blank match; scoring ignores capitalization.
                'short_answer_match' => 'exact',
            ]);

            $this->syncOptions($question, $data);
        });

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'Question added.');
    }

    public function edit(Request $request, Quiz $quiz, Question $question): View
    {
        $this->authorizeTeacher($request, $quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->load('options');

        return view('teacher.questions.edit', compact('quiz', 'question'));
    }

    public function update(StoreQuestionRequest $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $data = $request->validated();

        DB::transaction(function () use ($request, $question, $data) {
            if ($request->hasFile('image')) {
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }
                $question->image_path = $request->file('image')->store('questions', 'public');
            }

            $question->fill([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'] ?? $question->points,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? $question->time_limit_seconds,
                'sort_order' => $data['sort_order'] ?? $question->sort_order,
                'short_answer_expected' => $data['type'] === 'short_answer'
                    ? trim((string) ($data['short_answer_expected'] ?? ''))
                    : null,
                'short_answer_match' => 'exact',
            ])->save();

            $this->syncOptions($question, $data, replace: true);
        });

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'Question updated.');
    }

    public function destroy(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'Question deleted.');
    }

    public function reorder(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:questions,id'],
        ]);

        DB::transaction(function () use ($quiz, $data) {
            foreach (array_values($data['order']) as $index => $questionId) {
                Question::where('id', $questionId)
                    ->where('quiz_id', $quiz->id)
                    ->update(['sort_order' => $index]);
            }
        });

        return back()->with('status', 'Questions reordered.');
    }

    public function updateTimings(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeTeacher($request, $quiz);

        $data = $request->validate([
            'default_time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
            'apply_all_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
            'timings' => ['nullable', 'array'],
            'timings.*' => ['integer', 'min:5', 'max:600'],
        ]);

        if (isset($data['default_time_limit_seconds'])) {
            $quiz->update([
                'default_time_limit_seconds' => (int) $data['default_time_limit_seconds'],
            ]);
        }

        if (! empty($data['apply_all_seconds'])) {
            $quiz->questions()->update([
                'time_limit_seconds' => (int) $data['apply_all_seconds'],
            ]);

            return back()->with('status', "All questions set to {$data['apply_all_seconds']} seconds.");
        }

        foreach ($data['timings'] ?? [] as $questionId => $seconds) {
            Question::query()
                ->where('quiz_id', $quiz->id)
                ->where('id', (int) $questionId)
                ->update(['time_limit_seconds' => (int) $seconds]);
        }

        return back()->with('status', 'Question times saved.');
    }

    private function syncOptions(Question $question, array $data, bool $replace = false): void
    {
        if ($replace || in_array($question->type, ['mcq', 'true_false', 'short_answer'], true)) {
            $question->options()->delete();
        }

        if ($question->type === 'short_answer') {
            return;
        }

        $correctIndex = (int) ($data['correct_option'] ?? 0);

        if ($question->type === 'true_false') {
            $correctIndex = $correctIndex === 1 ? 1 : 0;
            $options = [
                ['option_text' => 'True', 'is_correct' => $correctIndex === 0],
                ['option_text' => 'False', 'is_correct' => $correctIndex === 1],
            ];
        } else {
            // Multiple choice: exactly 4 choices, one correct
            $raw = array_values($data['options'] ?? []);
            $options = [];
            for ($i = 0; $i < 4; $i++) {
                $text = trim((string) ($raw[$i]['option_text'] ?? ''));
                $options[] = [
                    'option_text' => $text,
                    'is_correct' => $correctIndex === $i,
                ];
            }
        }

        foreach ($options as $index => $option) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $option['option_text'],
                'is_correct' => (bool) $option['is_correct'],
                'sort_order' => $index,
            ]);
        }
    }

    private function authorizeTeacher(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->teacher_id === $request->user()->id, 403);
    }
}
