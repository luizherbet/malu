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

    public function test_build_command_includes_js_runtime_and_sleep(): void
    {
        config([
            'services.ytdlp.js_runtimes' => 'node',
            'services.ytdlp.sleep_requests' => 2,
            'services.ytdlp.cookies_file' => null,
            'services.ytdlp.cookies_from_browser' => null,
        ]);

        $download = Download::factory()->make();
        $service = app(YtDlpService::class);

        $command = $service->buildCommand($download, '/tmp/out.%(ext)s');

        $this->assertContains('--js-runtimes', $command);
        $this->assertContains('node', $command);
        $this->assertContains('--sleep-requests', $command);
        $this->assertContains('2', $command);
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
