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
            'data.requests' => ['required', 'array'],
        ];
    }
}
