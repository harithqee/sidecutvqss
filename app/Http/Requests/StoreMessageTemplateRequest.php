<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'trigger_event' => ['required', 'string', 'max:100'],
            'message_body' => ['required', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}