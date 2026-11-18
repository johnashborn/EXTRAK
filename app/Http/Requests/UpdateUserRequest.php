<?php

namespace App\Http\Requests;

class UpdateUserRequest extends InviteUserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['email']);

        return $rules;
    }
}
