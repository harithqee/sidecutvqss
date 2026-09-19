<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'trigger_event' => ['sometimes', 'required', 'string', 'max:100'],
            'message_body' => ['sometimes', 'required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}