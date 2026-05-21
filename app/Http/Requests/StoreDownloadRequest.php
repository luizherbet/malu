<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
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
