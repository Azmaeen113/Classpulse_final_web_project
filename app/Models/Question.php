<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'quiz_id',
        'type',
        'prompt',
        'image_path',
        'points',
        'time_limit_seconds',
        'sort_order',
        'short_answer_expected',
        'short_answer_match',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'time_limit_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function sessionResponses(): HasMany
    {
        return $this->hasMany(SessionResponse::class);
    }
}
