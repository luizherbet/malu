<?php

namespace App\Http\Resources;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'section' => $this->section,
            'download_playlist' => $this->download_playlist,
            'download_url' => $this->when(
                $this->status->value === 'done' && ! $this->download_playlist && $this->file_path !== null,
                fn () => route('jobs.file', ['download' => $this->id]),
            ),
            'status' => $this->status->value,
            'progress' => $this->progress,
            'error' => $this->error,
            'tracks' => $this->when(
                $this->status->value === 'done',
                fn () => $this->resolveTracks(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    private function resolveTracks(): array
    {
        if ($this->status->value !== 'done') {
            return [];
        }

        if ($this->download_playlist) {
            $directory = "downloads/{$this->id}";

            return collect($this->collectAudioFiles($directory))
                ->map(fn (string $path) => $this->trackEntry(basename($path), $path))
                ->values()
                ->all();
        }

        if ($this->file_path !== null && Storage::disk('local')->exists($this->file_path)) {
            return [$this->trackEntry(basename($this->file_path), $this->file_path)];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function collectAudioFiles(string $directory): array
    {
        $files = Storage::disk('local')->files($directory);

        $audio = array_values(array_filter(
            $files,
            static fn (string $file) => (bool) preg_match('/\.(mp3|m4a|opus|ogg)$/i', $file),
        ));

        sort($audio, SORT_NATURAL);

        return $audio;
    }

    /**
     * @return array{name: string, url: string}
     */
    private function trackEntry(string $name, string $storagePath): array
    {
        return [
            'name' => $name,
            'url' => route('jobs.file', [
                'download' => $this->id,
                'track' => $name,
            ]),
        ];
    }
}
