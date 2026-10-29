<?php

namespace App\Http\Requests\Prototype;

use Illuminate\Foundation\Http\FormRequest;

class StorePrototypeFileRequest extends FormRequest
{
    /** Largest upload, in kilobytes (20 MB). */
    public const MAX_KB = 20480;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.self::MAX_KB, 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'This file is larger than 20 MB.',
            'file.mimes' => 'Only photos (JPG, PNG, WebP, GIF), PDF, Word and Excel files can be attached.',
        ];
    }
}
