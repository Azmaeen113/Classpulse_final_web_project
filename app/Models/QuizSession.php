<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    protected $fillable = [
        'quiz_id',
        'classroom_id',
        'teacher_id',
        'status',
        'current_question_id',
        'question_started_at',
        'time_bonus_seconds',
        'reveal_answer',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'reveal_answer' => 'boolean',
            'question_started_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'time_bonus_seconds' => 'integer',
        ];
    }

    /** Current question duration including any live teacher extension. */
    public function effectiveTimeLimitSeconds(?Question $question = null): int
    {
        $question ??= $this->currentQuestion;
        if (! $question) {
            return 0;
        }

        return max(5, (int) $question->time_limit_seconds + (int) $this->time_bonus_seconds);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SessionResponse::class);
    }
}
