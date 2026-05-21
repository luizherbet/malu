<?php

namespace Tests\Feature;

use App\Enums\DownloadStatus;
use App\Exceptions\YtDlpException;
use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use App\Services\YtDlpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_lifecycle_from_api_to_file_download(): void
    {
        Storage::fake('local');

        $this->mock(YtDlpService::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andReturnUsing(function (Download $download, ?callable $onProgress): string {
                    if ($onProgress !== null) {
                        $onProgress(42);
                    }

                    $path = "downloads/{$download->id}/video.mp4";
                    Storage::disk('local')->put($path, 'fake video content');

                    return $path;
                });
        });

        $response = $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'format' => 'mp4',
            'quality' => '720p',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued');

        $download = Download::findOrFail($response->json('data.id'));

        ProcessDownloadJob::dispatchSync($download);

        $download->refresh();
        $this->assertSame(DownloadStatus::Done, $download->status);
        $this->assertSame(100, $download->progress);
        $this->assertNotNull($download->file_path);
        $this->assertNotNull($download->finished_at);

        $this->getJson("/api/jobs/{$download->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.file_name', 'video.mp4')
            ->assertJsonPath('data.download_url', route('jobs.file', $download->id));

        $fileResponse = $this->get("/api/jobs/{$download->id}/file");
        $fileResponse->assertOk();
        $this->assertStringContainsString('video.mp4', $fileResponse->headers->get('content-disposition', ''));
    }

    public function test_job_failure_surfaces_error_via_api(): void
    {
        $this->mock(YtDlpService::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andThrow(new YtDlpException('Simulated yt-dlp failure.'));
        });

        $download = Download::factory()->create([
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        ProcessDownloadJob::dispatchSync($download);

        $download->refresh();
        $this->assertSame(DownloadStatus::Failed, $download->status);
        $this->assertStringContainsString('Simulated', $download->error);

        $this->getJson("/api/jobs/{$download->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error', $download->error);

        $this->get("/api/jobs/{$download->id}/file")->assertNotFound();
    }

    public function test_stale_processing_job_is_expired_by_maintenance(): void
    {
        $download = Download::factory()->processing()->create([
            'updated_at' => now()->subMinutes(20),
        ]);

        $this->artisan('downloads:expire-stale')->assertSuccessful();

        $download->refresh();
        $this->assertSame(DownloadStatus::Failed, $download->status);
        $this->assertStringContainsString('timed out', $download->error);
    }
}
