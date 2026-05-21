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
            $this->markFailed($download, $e->getMessage());
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

        $this->markFailed($download, $exception?->getMessage() ?? 'Download job failed.');
    }

    private function markFailed(Download $download, string $message): void
    {
        $download->update([
            'status' => DownloadStatus::Failed,
            'error' => mb_substr($message, 0, 2000),
            'finished_at' => now(),
        ]);
    }
}
