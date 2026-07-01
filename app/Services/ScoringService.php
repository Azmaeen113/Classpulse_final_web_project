<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;

/**
 * Auto-grades answers against the teacher's stored correct answer.
 *
 * Kahoot-style points for a correct answer:
 *   score = base_points * (1 - (response_time_ms / (time_limit_seconds * 1000)) * 0.5)
 *
 * Incorrect answers always award 0 points.
 *
 * Fill-in-the-blank (short_answer) matching is exact but case-insensitive
 * (leading/trailing whitespace is trimmed only).
 */
class ScoringService
{
    public function evaluateCorrectness(
        Question $question,
        ?int $selectedOptionId = null,
        ?string $shortAnswerText = null
    ): bool {
        return match ($question->type) {
            'mcq', 'true_false' => $this->optionIsCorrect($question, $selectedOptionId),
            'short_answer' => $this->fillBlankIsCorrect($question, $shortAnswerText),
            default => false,
        };
    }

    public function calculatePoints(
        Question $question,
        bool $isCorrect,
        int $responseTimeMs,
        ?int $timeLimitSeconds = null
    ): int {
        if (! $isCorrect) {
            return 0;
        }

        $base = (int) $question->points;
        $limitSeconds = max(1, $timeLimitSeconds ?? (int) $question->time_limit_seconds);
        $limitMs = $limitSeconds * 1000;
        $clampedMs = max(0, min($responseTimeMs, $limitMs));

        $factor = 1 - (($clampedMs / $limitMs) * 0.5);
        $score = (int) round($base * $factor);

        $min = (int) round($base * 0.5);
        $max = $base;

        return max($min, min($max, $score));
    }

    public function score(
        Question $question,
        int $responseTimeMs,
        ?int $selectedOptionId = null,
        ?string $shortAnswerText = null,
        ?int $timeLimitSeconds = null
    ): array {
        $isCorrect = $this->evaluateCorrectness($question, $selectedOptionId, $shortAnswerText);
        $points = $this->calculatePoints($question, $isCorrect, $responseTimeMs, $timeLimitSeconds);

        return [
            'is_correct' => $isCorrect,
            'points_awarded' => $points,
        ];
    }

    private function optionIsCorrect(Question $question, ?int $selectedOptionId): bool
    {
        if ($selectedOptionId === null) {
            return false;
        }

        return QuestionOption::query()
            ->where('id', $selectedOptionId)
            ->where('question_id', $question->id)
            ->where('is_correct', true)
            ->exists();
    }

    private function fillBlankIsCorrect(Question $question, ?string $shortAnswerText): bool
    {
        if ($shortAnswerText === null || $question->short_answer_expected === null) {
            return false;
        }

        // Exact, case-insensitive match (trim surrounding whitespace only).
        $expected = trim((string) $question->short_answer_expected);
        $given = trim((string) $shortAnswerText);

        if ($expected === '' || $given === '') {
            return false;
        }

        return mb_strtolower($given, 'UTF-8') === mb_strtolower($expected, 'UTF-8');
    }
}
