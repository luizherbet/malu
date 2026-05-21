<?php

namespace App\Console\Commands;

use App\Services\DownloadMaintenanceService;
use Illuminate\Console\Command;

class CleanupDownloadFilesCommand extends Command
{
    protected $signature = 'downloads:cleanup';

    protected $description = 'Remove download files and records older than the retention period';

    public function handle(DownloadMaintenanceService $maintenance): int
    {
        $removed = $maintenance->cleanupExpiredFiles();

        $this->info("Cleaned up {$removed} download artifact(s).");

        return self::SUCCESS;
    }
}
