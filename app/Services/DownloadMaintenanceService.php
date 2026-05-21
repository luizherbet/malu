<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Models\Download;
use App\Support\MediaUrlValidator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DownloadMaintenanceService
{
    public function expireStaleDownloads(): int
    {
        $expired = 0;

        $queuedCutoff = Carbon::now()->subMinutes(config('malu.stale.queued_minutes', 30));
        $processingCutoff = Carbon::now()->subMinutes(config('malu.stale.processing_minutes', 15));

        $queued = Download::query()
            ->where('status', DownloadStatus::Queued)
            ->where('created_at', '<', $queuedCutoff)
            ->get();

        foreach ($queued as $download) {
            $this->markTimedOut($download, 'Download timed out while queued.');
            $expired++;
        }

        $processing = Download::query()
            ->where('status', DownloadStatus::Processing)
            ->where('updated_at', '<', $processingCutoff)
            ->get();

        foreach ($processing as $download) {
            $this->markTimedOut($download, 'Download timed out while processing.');
            $expired++;
        }

        return $expired;
    }

    public function cleanupExpiredFiles(): int
    {
        $cutoff = Carbon::now()->subHours(config('malu.retention_hours', 24));
        $removed = 0;

        $downloads = Download::query()
            ->whereNotNull('file_path')
            ->where(function ($query) use ($cutoff): void {
                $query->where('finished_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('finished_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            })
            ->get();

        foreach ($downloads as $download) {
            if ($this->deleteDownloadFiles($download)) {
                $removed++;
            }

            if (config('malu.prune_records', true)) {
                $download->delete();
            } else {
                $download->update(['file_path' => null]);
            }
        }

        $removed += $this->cleanupOrphanedDirectories();

        return $removed;
    }

    private function markTimedOut(Download $download, string $message): void
    {
        $download->update([
            'status' => DownloadStatus::Failed,
            'error' => $message,
            'finished_at' => now(),
        ]);

        $this->deleteDownloadFiles($download);
    }

    private function deleteDownloadFiles(Download $download): bool
    {
        if ($download->file_path === null || ! MediaUrlValidator::isSafeStoragePath($download->file_path)) {
            return false;
        }

        $disk = Storage::disk('local');
        $deleted = false;

        if ($disk->exists($download->file_path)) {
            $disk->delete($download->file_path);
            $deleted = true;
        }

        $directory = dirname($download->file_path);

        if ($directory !== '.' && $directory !== 'downloads' && $disk->exists($directory)) {
            $remaining = $disk->allFiles($directory);

            if ($remaining === []) {
                $disk->deleteDirectory($directory);
            }
        }

        return $deleted;
    }

    private function cleanupOrphanedDirectories(): int
    {
        $disk = Storage::disk('local');
        $removed = 0;

        if (! $disk->exists('downloads')) {
            return 0;
        }

        foreach ($disk->directories('downloads') as $directory) {
            if ($disk->allFiles($directory) !== []) {
                continue;
            }

            $hasRecord = Download::query()
                ->where('file_path', 'like', $directory.'/%')
                ->exists();

            if (! $hasRecord) {
                $disk->deleteDirectory($directory);
                $removed++;
            }
        }

        return $removed;
    }
}
