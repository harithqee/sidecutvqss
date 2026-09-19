<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQueueTicketStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['in_queue', 'serving', 'completed', 'canceled'])],
        ];
    }
}
