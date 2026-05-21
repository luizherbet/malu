<?php

namespace App\Http\Requests;

use App\Rules\AllowedMediaUrl;
use App\Support\MediaUrlValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDownloadRequest extends FormRequest
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
            'format' => ['sometimes', 'string', Rule::in(['mp4', 'mp3'])],
            'quality' => [
                'sometimes',
                'string',
                Rule::in(['best', '720p', '1080p']),
                Rule::excludeIf(fn () => $this->input('format', 'mp4') === 'mp3'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['format'] ??= 'mp4';
        $data['quality'] ??= 'best';

        if ($data['format'] === 'mp3') {
            $data['quality'] = 'best';
        }

        return $data;
    }
}
