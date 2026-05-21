<?php

namespace Tests\Feature;

use App\Enums\DownloadStatus;
use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_download_and_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'format' => 'mp4',
            'quality' => '720p',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.format', 'mp4')
            ->assertJsonPath('data.quality', '720p')
            ->assertJsonPath('data.progress', 0);

        $download = Download::first();
        $this->assertNotNull($download);
        Queue::assertPushed(ProcessDownloadJob::class, fn (ProcessDownloadJob $job) => $job->download->is($download));
    }

    public function test_store_validates_url(): void
    {
        $response = $this->postJson('/api/jobs', [
            'url' => 'not-a-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_store_rejects_local_urls(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/jobs', [
            'url' => 'https://127.0.0.1/video',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_store_is_rate_limited(): void
    {
        Queue::fake();
        config(['malu.rate_limit.store' => 2]);

        $payload = ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'];

        $this->postJson('/api/jobs', $payload)->assertCreated();
        $this->postJson('/api/jobs', $payload)->assertCreated();
        $this->postJson('/api/jobs', $payload)->assertStatus(429);
    }

    public function test_show_returns_download_status(): void
    {
        $download = Download::factory()->processing()->create();

        $response = $this->getJson("/api/jobs/{$download->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $download->id)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_file_downloads_when_ready(): void
    {
        Storage::fake('local');
        $path = 'downloads/test/video.mp4';
        Storage::disk('local')->put($path, 'fake video content');

        $download = Download::factory()->done()->create([
            'file_path' => $path,
        ]);

        $response = $this->get("/api/jobs/{$download->id}/file");

        $response->assertOk();
        $this->assertStringContainsString('video.mp4', $response->headers->get('content-disposition', ''));
    }

    public function test_file_returns_404_when_not_ready(): void
    {
        $download = Download::factory()->create([
            'status' => DownloadStatus::Queued,
        ]);

        $this->get("/api/jobs/{$download->id}/file")->assertNotFound();
    }
}
