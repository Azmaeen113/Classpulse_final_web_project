<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAiQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:3', 'max:200'],
            'count' => ['nullable', 'integer', 'min:1', 'max:15'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic.required' => 'Please enter a topic for AI question generation.',
            'topic.min' => 'Topic must be at least 3 characters.',
            'count.max' => 'You can generate at most 15 questions at a time.',
        ];
    }
}
