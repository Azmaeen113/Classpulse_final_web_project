<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'room_code' => ['required', 'string', 'size:6'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('room_code')) {
            $this->merge([
                'room_code' => strtoupper(trim((string) $this->input('room_code'))),
            ]);
        }
    }
}
