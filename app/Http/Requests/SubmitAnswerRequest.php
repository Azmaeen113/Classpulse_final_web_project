<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'short_answer_text' => ['nullable', 'string', 'max:1000'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
            'is_auto_submit' => ['sometimes', 'boolean'],
        ];
    }
}
