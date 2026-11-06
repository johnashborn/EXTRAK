<?php

namespace App\Http\Requests\Prototype;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrototypeStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.requests' => ['present', 'array'],
            'data.requests.*.id' => ['required', 'string', 'max:50', 'distinct'],
            'data.requests.*.code' => ['required', 'string', 'max:30', 'distinct'],
            'data.requests.*.title' => ['present', 'nullable', 'string', 'max:255'],
            'data.requests.*.status' => ['required', 'string', 'max:20'],
            'data.requests.*.start' => ['nullable', 'date'],
            'data.requests.*.end' => ['nullable', 'date'],
            'data.requests.*.events' => ['array'],
            'data.requests.*.events.*.at' => ['required', 'date'],
            'data.requests.*.events.*.to' => ['required', 'string', 'max:20'],
            'data.requests.*.report' => ['nullable', 'array'],
            'data.requests.*.report.photos' => ['array'],
            'data.notifications' => ['array'],
            'data.notifications.*.at' => ['required', 'date'],
        ];
    }
}
