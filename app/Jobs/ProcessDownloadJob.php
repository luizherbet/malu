<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Exceptions\YtDlpException;
use App\Models\Download;
use App\Services\YtDlpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDownloadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout;

    public int $tries = 1;

    public function __construct(public Download $download)
    {
        $this->timeout = config('services.ytdlp.timeout', 600) + 30;
    }

    public function handle(YtDlpService $ytDlp): void
    {
        $download = $this->download->fresh();

        if ($download === null || $download->isTerminal()) {
            return;
        }

        $download->update([
            'status' => DownloadStatus::Processing,
            'progress' => 0,
            'error' => null,
            'finished_at' => null,
        ]);

        try {
            $filePath = $ytDlp->run($download, function (int $progress) use ($download): void {
                if ($progress > $download->progress) {
                    $download->update(['progress' => $progress]);
                }
            });

            $download->update([
                'status' => DownloadStatus::Done,
                'progress' => 100,
                'file_path' => $filePath,
                'error' => null,
                'finished_at' => now(),
            ]);
        } catch (YtDlpException $e) {
            $this->markFailed($download, $this->formatErrorMessage($e->getMessage()));
        }
    }

    public function failed(?Throwable $exception): void
    {
        $download = $this->download->fresh();

        if ($download === null || $download->isTerminal()) {
            return;
        }

        Log::error('ProcessDownloadJob failed', [
            'download_id' => $download->id,
            'message' => $exception?->getMessage(),
        ]);

        $this->markFailed(
            $download,
            $this->formatErrorMessage($exception?->getMessage() ?? 'Download job failed.'),
        );
    }

    private function markFailed(Download $download, string $message): void
    {
        $download->update([
            'status' => DownloadStatus::Failed,
            'error' => mb_substr($message, 0, 2000),
            'finished_at' => now(),
        ]);
    }

    private function formatErrorMessage(string $message): string
    {
        if (str_contains($message, 'Sign in to confirm') || str_contains($message, 'not a bot')) {
            return $message.' Configure cookies: see docs/YOUTUBE.md (YTDLP_COOKIES_FILE).';
        }

        if (str_contains($message, 'HTTP Error 429')) {
            return $message.' YouTube rate limit — wait a few minutes or use cookies (docs/YOUTUBE.md).';
        }

        return $message;
    }
}
