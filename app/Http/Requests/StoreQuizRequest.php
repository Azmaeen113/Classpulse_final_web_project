<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => [
                'required',
                'integer',
                Rule::exists('classrooms', 'id')->where('teacher_id', $this->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'default_time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
        ];
    }
}
