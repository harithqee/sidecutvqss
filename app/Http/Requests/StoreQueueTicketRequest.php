<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-]{7,15}$/'],
            'barber_id' => ['nullable', 'exists:barbers,id'],
            'service_id' => ['nullable', 'exists:services,id'],
        ];
    }
}
