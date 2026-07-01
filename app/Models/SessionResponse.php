<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionResponse extends Model
{
    protected $fillable = [
        'quiz_session_id',
        'question_id',
        'student_id',
        'selected_option_id',
        'short_answer_text',
        'is_correct',
        'points_awarded',
        'response_time_ms',
        'is_auto_submit',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'is_auto_submit' => 'boolean',
            'points_awarded' => 'integer',
            'response_time_ms' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
