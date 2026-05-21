<?php

namespace App\Services;

use App\Exceptions\YtDlpException;
use App\Models\Download;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class YtDlpService
{
    public function run(Download $download, ?callable $onProgress = null): string
    {
        $directory = $this->directoryFor($download);
        Storage::disk('local')->makeDirectory($directory);

        $outputTemplate = $this->outputTemplate($download, $directory);
        $command = $this->buildCommand($download, $outputTemplate);

        $timeout = $download->download_playlist
            ? config('services.ytdlp.playlist_timeout', 3600)
            : config('services.ytdlp.timeout', 600);

        $result = Process::timeout($timeout)->run(
            $command,
            function (string $type, string $buffer) use ($onProgress): void {
                if ($type === 'err') {
                    $this->parseProgress($buffer, $onProgress);
                }
            },
        );

        if ($result->failed()) {
            throw YtDlpException::fromProcess(
                $result->exitCode() ?? 1,
                $result->errorOutput() ?: $result->output(),
            );
        }

        return $this->finalizeOutput($download, $directory);
    }

    /**
     * @return list<string>
     */
    public function buildCommand(Download $download, string $outputTemplate): array
    {
        $binary = config('services.ytdlp.binary', 'yt-dlp');

        return array_merge(
            [$binary],
            $this->playlistOptions($download),
            $this->extractorArguments(),
            $this->audioFormatOptions(),
            ['-o', $outputTemplate],
            [$download->url],
        );
    }

    /**
     * @return list<string>
     */
    public function isMultiVideoPlaylist(string $url): bool
    {
        return preg_match('/[?&]list=[^&]+/i', $url) === 1;
    }

    /**
     * @return list<string>
     */
    public function extractorArguments(): array
    {
        $options = [];

        $jsRuntimes = config('services.ytdlp.js_runtimes');
        if (filled($jsRuntimes)) {
            $options[] = '--js-runtimes';
            $options[] = $jsRuntimes;
        }

        $cookiesPath = $this->resolveCookiesPath();
        if ($cookiesPath !== null) {
            $options[] = '--cookies';
            $options[] = $cookiesPath;
        } elseif (filled($browser = config('services.ytdlp.cookies_from_browser'))) {
            $options[] = '--cookies-from-browser';
            $options[] = $browser;
        }

        $sleepRequests = config('services.ytdlp.sleep_requests');
        if ($sleepRequests > 0) {
            $options[] = '--sleep-requests';
            $options[] = (string) $sleepRequests;
        }

        return $options;
    }

    private function playlistOptions(Download $download): array
    {
        if ($download->download_playlist) {
            $options = ['--newline', '--no-overwrites', '--ignore-errors'];

            if ($this->isMultiVideoPlaylist($download->url)) {
                $options[] = '--yes-playlist';

                return $options;
            }

            $options[] = '--no-playlist';
            $options[] = '--split-chapters';

            return $options;
        }

        $options = ['--no-playlist', '--newline', '--no-overwrites'];

        if (filled($download->section)) {
            $options[] = '--download-sections';
            $options[] = $download->section;
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function audioFormatOptions(): array
    {
        return [
            '-f', 'bestaudio/best',
            '-x', '--audio-format', 'mp3',
            '--audio-quality', '0',
        ];
    }

    private function outputTemplate(Download $download, string $directory): string
    {
        $base = Storage::disk('local')->path($directory).'/';

        if (! $download->download_playlist) {
            return $base.'%(title).200B [%(id)s].%(ext)s';
        }

        if ($this->isMultiVideoPlaylist($download->url)) {
            return $base.'%(playlist_autonumber)03d-%(title).200B [%(id)s].%(ext)s';
        }

        return $base.'%(section_number)03d-%(section_title).200B [%(id)s].%(ext)s';
    }

    private function directoryFor(Download $download): string
    {
        return "downloads/{$download->id}";
    }

    private function finalizeOutput(Download $download, string $directory): string
    {
        $files = $this->collectAudioFiles($directory);

        if ($files === []) {
            throw new YtDlpException('No audio files were produced by yt-dlp.');
        }

        if ($download->download_playlist) {
            if (count($files) === 1) {
                throw new YtDlpException(
                    'Only one track was downloaded. Use a playlist URL (?list=...) with multiple videos, '
                    .'or a single video that has chapters (album).',
                );
            }

            return $directory;
        }

        if (count($files) > 1) {
            throw new YtDlpException('Expected a single audio file. Try enabling playlist mode for multiple tracks.');
        }

        return $files[0];
    }

    /**
     * @return list<string>
     */
    public function collectAudioFiles(string $directory): array
    {
        $files = Storage::disk('local')->files($directory);

        $audio = array_values(array_filter(
            $files,
            static fn (string $file) => (bool) preg_match('/\.(mp3|m4a|opus|ogg)$/i', $file),
        ));

        sort($audio, SORT_NATURAL);

        return $audio;
    }

    private function parseProgress(string $output, ?callable $onProgress): void
    {
        if ($onProgress === null) {
            return;
        }

        if (preg_match_all('/\[download\]\s+(\d+(?:\.\d+)?)%/', $output, $matches)) {
            $percent = (int) round((float) end($matches[1]));
            $onProgress(min(99, max(0, $percent)));
        }
    }

    private function resolveCookiesPath(): ?string
    {
        $cookiesFile = config('services.ytdlp.cookies_file');
        if (! filled($cookiesFile)) {
            return null;
        }

        $path = str_starts_with($cookiesFile, '/')
            ? $cookiesFile
            : Storage::disk('local')->path($cookiesFile);

        return is_file($path) ? $path : null;
    }
}
