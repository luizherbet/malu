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
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.progress', 0);

        $download = Download::first();
        $this->assertNotNull($download);
        $this->assertSame('mp3', $download->format);
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

    public function test_store_accepts_playlist_flag(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/jobs', [
            'url' => 'https://www.youtube.com/playlist?list=PLtest123',
            'download_playlist' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.download_playlist', true);

        $this->assertTrue(Download::first()->download_playlist);
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

    public function test_file_downloads_single_track(): void
    {
        Storage::fake('local');
        $path = 'downloads/test/song.mp3';
        Storage::disk('local')->put($path, 'fake audio');

        $download = Download::factory()->done()->create([
            'file_path' => $path,
            'download_playlist' => false,
        ]);

        $response = $this->get("/api/jobs/{$download->id}/file");

        $response->assertOk();
        $this->assertStringContainsString('song.mp3', $response->headers->get('content-disposition', ''));
    }

    public function test_file_downloads_playlist_track_by_name(): void
    {
        Storage::fake('local');

        $download = Download::factory()->done()->create([
            'download_playlist' => true,
        ]);

        $dir = "downloads/{$download->id}";
        Storage::disk('local')->put("{$dir}/001-track.mp3", 'one');
        Storage::disk('local')->put("{$dir}/002-track.mp3", 'two');

        $download->update(['file_path' => $dir]);

        $response = $this->get("/api/jobs/{$download->id}/file?track=002-track.mp3");

        $response->assertOk();
        $this->assertStringContainsString('002-track.mp3', $response->headers->get('content-disposition', ''));
    }

    public function test_show_lists_separate_tracks_when_playlist_done(): void
    {
        Storage::fake('local');

        $download = Download::factory()->done()->create([
            'download_playlist' => true,
        ]);

        $dir = "downloads/{$download->id}";
        Storage::disk('local')->put("{$dir}/001-a.mp3", 'a');
        Storage::disk('local')->put("{$dir}/002-b.mp3", 'b');

        $download->update(['file_path' => $dir]);

        $response = $this->getJson("/api/jobs/{$download->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.tracks')
            ->assertJsonPath('data.tracks.0.name', '001-a.mp3')
            ->assertJsonPath('data.tracks.1.name', '002-b.mp3');
    }

    public function test_file_returns_404_when_not_ready(): void
    {
        $download = Download::factory()->create([
            'status' => DownloadStatus::Queued,
        ]);

        $this->get("/api/jobs/{$download->id}/file")->assertNotFound();
    }
}
