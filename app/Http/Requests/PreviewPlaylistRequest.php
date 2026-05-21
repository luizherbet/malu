<?php

namespace App\Http\Requests;

use App\Rules\AllowedMediaUrl;
use App\Support\MediaUrlValidator;
use Illuminate\Foundation\Http\FormRequest;

class PreviewPlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('url') && is_string($this->input('url'))) {
            $this->merge([
                'url' => MediaUrlValidator::sanitize($this->input('url')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', new AllowedMediaUrl],
        ];
    }
}
