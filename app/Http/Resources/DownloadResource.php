<?php

namespace App\Http\Resources;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Download */
class DownloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'format' => $this->format,
            'quality' => $this->quality,
            'error' => $this->error,
            'file_name' => $this->file_path ? basename($this->file_path) : null,
            'download_url' => $this->when(
                $this->status->value === 'done' && $this->file_path,
                fn () => route('jobs.file', $this->id),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
        ];
    }
}
