<?php

namespace App\Console\Commands;

use App\Services\DownloadMaintenanceService;
use Illuminate\Console\Command;

class ExpireStaleDownloadsCommand extends Command
{
    protected $signature = 'downloads:expire-stale';

    protected $description = 'Mark queued or processing downloads that exceeded their timeout as failed';

    public function handle(DownloadMaintenanceService $maintenance): int
    {
        $expired = $maintenance->expireStaleDownloads();

        $this->info("Expired {$expired} stale download(s).");

        return self::SUCCESS;
    }
}
