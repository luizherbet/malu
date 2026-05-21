<?php

namespace Tests\Unit;

use App\Models\Download;
use App\Services\YtDlpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YtDlpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_command_always_uses_mp3_audio_options(): void
    {
        config([
            'services.ytdlp.js_runtimes' => 'node',
            'services.ytdlp.sleep_requests' => 1,
            'services.ytdlp.cookies_file' => null,
            'services.ytdlp.cookies_from_browser' => null,
        ]);

        $download = Download::factory()->make();
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/out.%(ext)s');

        $this->assertContains('-f', $command);
        $this->assertContains('bestaudio/best', $command);
        $this->assertContains('--audio-format', $command);
        $this->assertContains('mp3', $command);
        $this->assertStringNotContainsString('bv*', implode(' ', $command));
    }

    public function test_build_command_uses_download_sections_for_chapter(): void
    {
        $download = Download::factory()->make([
            'section' => 'Track One',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/out.%(ext)s');

        $this->assertContains('--download-sections', $command);
        $this->assertContains('Track One', $command);
        $this->assertContains('--no-playlist', $command);
    }

    public function test_build_command_uses_yes_playlist_for_list_urls(): void
    {
        $download = Download::factory()->make([
            'download_playlist' => true,
            'url' => 'https://www.youtube.com/playlist?list=PLtest123',
        ]);
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/%(autonumber)s.%(ext)s');

        $this->assertContains('--yes-playlist', $command);
        $this->assertNotContains('--split-chapters', $command);
    }

    public function test_build_command_splits_chapters_for_single_video_album(): void
    {
        $download = Download::factory()->make([
            'download_playlist' => true,
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/%(section_number)s.%(ext)s');

        $this->assertContains('--split-chapters', $command);
        $this->assertContains('--no-playlist', $command);
        $this->assertNotContains('--yes-playlist', $command);
    }

    public function test_finalize_output_returns_directory_for_playlist_with_multiple_tracks(): void
    {
        Storage::fake('local');

        $directory = 'downloads/playlist-test';
        Storage::disk('local')->put("{$directory}/001-first.mp3", 'one');
        Storage::disk('local')->put("{$directory}/002-second.mp3", 'two');

        $download = Download::factory()->make(['download_playlist' => true]);
        $service = app(YtDlpService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('finalizeOutput');
        $method->setAccessible(true);

        $result = $method->invoke($service, $download, $directory);

        $this->assertSame($directory, $result);
        Storage::disk('local')->assertExists("{$directory}/001-first.mp3");
        Storage::disk('local')->assertExists("{$directory}/002-second.mp3");
    }

    public function test_build_command_includes_cookies_file_when_present(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cookies/youtube.txt', '# Netscape HTTP Cookie File');

        config([
            'services.ytdlp.cookies_file' => 'cookies/youtube.txt',
            'services.ytdlp.cookies_from_browser' => null,
        ]);

        $download = Download::factory()->make();
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/out.%(ext)s');

        $this->assertContains('--cookies', $command);
        $this->assertTrue(
            collect($command)->contains(fn (string $arg) => str_ends_with($arg, 'cookies/youtube.txt')),
        );
    }
}
