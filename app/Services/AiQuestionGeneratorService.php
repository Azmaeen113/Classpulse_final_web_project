<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Calls OpenAI Chat Completions to generate ClassPulse quiz questions from a topic.
 */
class AiQuestionGeneratorService
{
    /**
     * @return list<array{
     *   type: string,
     *   prompt: string,
     *   points: int,
     *   time_limit_seconds: int,
     *   options?: list<array{option_text: string, is_correct: bool}>,
     *   short_answer_expected?: string|null,
     *   short_answer_match?: string
     * }>
     */
    public function generate(string $topic, int $count = 5, string $difficulty = 'medium'): array
    {
        $apiKey = config('services.openai.key');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        if (! $apiKey) {
            throw new RuntimeException('AI generation is unavailable.');
        }

        $count = max(1, min(15, $count));
        $difficulty = in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium';

        $system = <<<'PROMPT'
You are an expert classroom quiz author for ClassPulse, a live quiz app for schools.
Return ONLY valid JSON (no markdown) matching this schema:
{
  "questions": [
    {
      "type": "mcq" | "true_false" | "short_answer",
      "prompt": "string — clear question text",
      "points": 100,
      "time_limit_seconds": 30,
      "options": [
        { "option_text": "string", "is_correct": true|false }
      ],
      "short_answer_expected": "string or null"
    }
  ]
}

Rules:
- Allowed types ONLY: mcq (multiple choice), true_false, short_answer (fill in the blank).
- Mix types when count >= 3: mostly mcq, include at least one true_false and one short_answer when possible.
- MCQ: exactly 4 options, exactly one is_correct true.
- true_false: exactly 2 options with texts "True" and "False", exactly one correct.
- short_answer (fill in the blank): options must be [], set short_answer_expected to the clear correct string.
  Matching is case-insensitive and trims surrounding spaces.
- Age-appropriate for secondary / early university students.
- Factual, unambiguous, suitable for automatic live classroom scoring.
- points between 50 and 200; time_limit_seconds between 15 and 60.
PROMPT;

        $user = "Topic: {$topic}\nDifficulty: {$difficulty}\nGenerate exactly {$count} questions.";

        $verify = true;
        $caBundle = storage_path('certs/cacert.pem');
        if (is_file($caBundle)) {
            $verify = $caBundle;
        } elseif (app()->environment('local')) {
            // Local Windows/XAMPP often lacks a CA bundle; allow generate in local only.
            $verify = false;
        }

        $response = Http::timeout(90)
            ->withOptions(['verify' => $verify])
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('AI question generation failed', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException('AI generation request failed.');
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('AI generation returned an empty response.');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI generation returned invalid JSON.');
        }

        $raw = $decoded['questions'] ?? $decoded;
        if (! is_array($raw)) {
            throw new RuntimeException('AI generation JSON did not include a questions array.');
        }

        return $this->normalizeQuestions($raw);
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<array<string, mixed>>
     */
    private function normalizeQuestions(array $raw): array
    {
        $out = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = $item['type'] ?? 'mcq';
            if (! in_array($type, ['mcq', 'true_false', 'short_answer'], true)) {
                $type = 'mcq';
            }

            $prompt = trim((string) ($item['prompt'] ?? ''));
            if ($prompt === '') {
                continue;
            }

            $points = (int) ($item['points'] ?? 100);
            $points = max(50, min(200, $points));

            $time = (int) ($item['time_limit_seconds'] ?? 30);
            $time = max(15, min(60, $time));

            $row = [
                'type' => $type,
                'prompt' => $prompt,
                'points' => $points,
                'time_limit_seconds' => $time,
                'short_answer_expected' => null,
                'short_answer_match' => 'exact',
                'options' => [],
            ];

            if ($type === 'short_answer') {
                $row['short_answer_expected'] = trim((string) ($item['short_answer_expected'] ?? ''));
                $row['short_answer_match'] = 'exact'; // case-insensitive exact match
                if ($row['short_answer_expected'] === '') {
                    continue;
                }
            } else {
                $options = $item['options'] ?? [];
                if (! is_array($options)) {
                    $options = [];
                }

                if ($type === 'true_false') {
                    $correctTrue = true;
                    foreach ($options as $opt) {
                        if (! is_array($opt)) {
                            continue;
                        }
                        $text = trim((string) ($opt['option_text'] ?? $opt['text'] ?? ''));
                        if (strcasecmp($text, 'False') === 0 && ! empty($opt['is_correct'])) {
                            $correctTrue = false;
                            break;
                        }
                        if (strcasecmp($text, 'True') === 0 && ! empty($opt['is_correct'])) {
                            $correctTrue = true;
                            break;
                        }
                    }
                    if (array_key_exists('correct', $item)) {
                        $correctTrue = (bool) $item['correct'];
                    }
                    $normalizedOptions = [
                        ['option_text' => 'True', 'is_correct' => $correctTrue],
                        ['option_text' => 'False', 'is_correct' => ! $correctTrue],
                    ];
                } else {
                    $normalizedOptions = [];
                    $hasCorrect = false;
                    foreach (array_values($options) as $opt) {
                        if (! is_array($opt)) {
                            continue;
                        }
                        $text = trim((string) ($opt['option_text'] ?? $opt['text'] ?? ''));
                        if ($text === '') {
                            continue;
                        }
                        $isCorrect = (bool) ($opt['is_correct'] ?? false);
                        if ($isCorrect) {
                            $hasCorrect = true;
                        }
                        $normalizedOptions[] = [
                            'option_text' => $text,
                            'is_correct' => $isCorrect,
                        ];
                        if (count($normalizedOptions) >= 4) {
                            break;
                        }
                    }

                    // MCQ must have exactly 4 choices
                    if (count($normalizedOptions) !== 4) {
                        continue;
                    }

                    if (! $hasCorrect) {
                        $normalizedOptions[0]['is_correct'] = true;
                    }

                    $seenCorrect = false;
                    foreach ($normalizedOptions as $i => $opt) {
                        if ($opt['is_correct']) {
                            if ($seenCorrect) {
                                $normalizedOptions[$i]['is_correct'] = false;
                            } else {
                                $seenCorrect = true;
                            }
                        }
                    }
                }

                $row['options'] = $normalizedOptions;
            }

            $out[] = $row;
        }

        return $out;
    }
}
