<?php

namespace App\Http\Requests;

use App\Rules\AllowedMediaUrl;
use App\Support\MediaUrlValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null || ! config('malu.require_auth');
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
            'section' => ['sometimes', 'nullable', 'string', 'max:500'],
            'download_playlist' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['download_playlist'] ??= false;
        $data['section'] = filled($data['section'] ?? null) ? $data['section'] : null;
        $data['format'] = 'mp3';
        $data['quality'] = 'best';

        return $data;
    }
}
