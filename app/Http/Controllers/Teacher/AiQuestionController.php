<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAiQuestionsRequest;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Services\ActivityLogService;
use App\Services\AiQuestionGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AiQuestionController extends Controller
{
    public function create(Request $request, Quiz $quiz): View
    {
        $this->authorizeTeacher($request, $quiz);

        return view('teacher.questions.ai-generate', compact('quiz'));
    }

    public function store(
        GenerateAiQuestionsRequest $request,
        Quiz $quiz,
        AiQuestionGeneratorService $generator,
        ActivityLogService $activity
    ): RedirectResponse {
        $this->authorizeTeacher($request, $quiz);

        $topic = $request->validated('topic');
        $count = (int) ($request->validated('count') ?? 5);
        $difficulty = $request->validated('difficulty') ?? 'medium';

        try {
            $questions = $generator->generate($topic, $count, $difficulty);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['topic' => "Couldn't generate questions right now, try again."]);
        }

        if ($questions === []) {
            return back()
                ->withInput()
                ->withErrors(['topic' => 'AI did not return any usable questions. Try a clearer topic.']);
        }

        $sort = (int) $quiz->questions()->max('sort_order');

        $defaultTime = (int) ($quiz->default_time_limit_seconds ?: 30);
        // Optional override from the AI form; otherwise quiz default (same rules as manual questions)
        $forceTime = $request->filled('time_limit_seconds')
            ? max(5, min(600, (int) $request->input('time_limit_seconds')))
            : null;

        DB::transaction(function () use ($quiz, $questions, &$sort, $defaultTime, $forceTime) {
            foreach ($questions as $item) {
                $sort++;

                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'type' => $item['type'],
                    'prompt' => $item['prompt'],
                    'points' => $item['points'],
                    'time_limit_seconds' => $forceTime ?? $defaultTime,
                    'sort_order' => $sort,
                    'short_answer_expected' => $item['type'] === 'short_answer'
                        ? ($item['short_answer_expected'] ?? null)
                        : null,
                    'short_answer_match' => 'exact',
                ]);

                if (in_array($item['type'], ['mcq', 'true_false'], true)) {
                    foreach ($item['options'] ?? [] as $index => $option) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) $option['is_correct'],
                            'sort_order' => $index,
                        ]);
                    }
                }
            }
        });

        $activity->log(
            $request->user(),
            'quiz.ai_questions',
            'AI generated '.count($questions)." questions for quiz {$quiz->title} (topic: {$topic})",
            $request
        );

        return redirect()
            ->route('teacher.quizzes.show', $quiz)
            ->with('status', 'AI Question Generation added '.count($questions).' question(s) about "'.$topic.'". Review them before going live.');
    }

    private function authorizeTeacher(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->teacher_id === $request->user()->id, 403);
    }
}
