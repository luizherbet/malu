<?php

namespace Tests\Unit;

use App\Enums\DownloadStatus;
use App\Models\Download;
use App\Services\DownloadMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadMaintenanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private DownloadMaintenanceService $maintenance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenance = app(DownloadMaintenanceService::class);
        Storage::fake('local');
        config([
            'malu.stale.queued_minutes' => 30,
            'malu.stale.processing_minutes' => 15,
            'malu.retention_hours' => 24,
            'malu.prune_records' => true,
        ]);
    }

    public function test_expires_stale_queued_downloads(): void
    {
        Carbon::setTestNow('2026-05-21 12:00:00');

        $download = Download::factory()->create([
            'status' => DownloadStatus::Queued,
            'created_at' => now()->subMinutes(31),
        ]);

        $expired = $this->maintenance->expireStaleDownloads();

        $this->assertSame(1, $expired);
        $download->refresh();
        $this->assertSame(DownloadStatus::Failed, $download->status);
        $this->assertStringContainsString('timed out', $download->error);
    }

    public function test_expires_stale_processing_downloads(): void
    {
        Carbon::setTestNow('2026-05-21 12:00:00');

        $download = Download::factory()->processing()->create([
            'updated_at' => now()->subMinutes(16),
        ]);

        $expired = $this->maintenance->expireStaleDownloads();

        $this->assertSame(1, $expired);
        $download->refresh();
        $this->assertSame(DownloadStatus::Failed, $download->status);
    }

    public function test_cleans_up_old_files_and_records(): void
    {
        Carbon::setTestNow('2026-05-21 12:00:00');

        $download = Download::factory()->done()->create([
            'download_playlist' => true,
            'finished_at' => now()->subHours(25),
        ]);

        $dir = "downloads/{$download->id}";
        Storage::disk('local')->put("{$dir}/track.mp3", 'content');
        $download->update(['file_path' => $dir]);

        $removed = $this->maintenance->cleanupExpiredFiles();

        $this->assertSame(1, $removed);
        $this->assertNull(Download::find($download->id));
        Storage::disk('local')->assertMissing("{$dir}/track.mp3");
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
