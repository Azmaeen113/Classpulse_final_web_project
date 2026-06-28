<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['mcq', 'true_false', 'short_answer'])],
            'prompt' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'points' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'short_answer_expected' => ['nullable', 'required_if:type,short_answer', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['nullable', 'string', 'max:255'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
            'correct_option' => ['nullable', 'integer', 'min:0', 'max:3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');

            if ($type === 'mcq') {
                $options = array_values($this->input('options', []));
                $texts = [];
                foreach ($options as $option) {
                    $text = trim((string) ($option['option_text'] ?? ''));
                    if ($text !== '') {
                        $texts[] = $text;
                    }
                }

                if (count($texts) !== 4) {
                    $validator->errors()->add('options', 'Multiple choice questions must have exactly 4 answer choices.');
                }

                if ($this->input('correct_option') === null || $this->input('correct_option') === '') {
                    $validator->errors()->add('correct_option', 'Select the correct choice (A–D).');
                }
            }

            if ($type === 'true_false' && ($this->input('correct_option') === null || $this->input('correct_option') === '')) {
                $validator->errors()->add('correct_option', 'Select True or False as the correct answer.');
            }

            if ($type === 'short_answer') {
                $expected = trim((string) $this->input('short_answer_expected', ''));
                if ($expected === '') {
                    $validator->errors()->add('short_answer_expected', 'Enter the expected fill-in-the-blank answer.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Question type must be multiple choice, true/false, or fill in the blank.',
            'short_answer_expected.required_if' => 'Enter the expected fill-in-the-blank answer.',
        ];
    }
}
