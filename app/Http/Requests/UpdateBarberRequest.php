<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'role' => ['sometimes', 'required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
