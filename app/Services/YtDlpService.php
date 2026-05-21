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

        $outputTemplate = Storage::disk('local')->path("{$directory}/%(id)s.%(ext)s");
        $command = $this->buildCommand($download, $outputTemplate);

        $timeout = config('services.ytdlp.timeout', 600);
        $process = Process::timeout($timeout)->start($command);

        while ($process->running()) {
            $this->parseProgress($process->latestErrorOutput(), $onProgress);
            usleep(250_000);
        }

        $stderr = $process->errorOutput();
        $this->parseProgress($stderr, $onProgress);

        if (! $process->successful()) {
            throw YtDlpException::fromProcess($process->exitCode() ?? 1, $stderr ?: $process->output());
        }

        return $this->resolveOutputFile($directory);
    }

    /**
     * @return list<string>
     */
    public function buildCommand(Download $download, string $outputTemplate): array
    {
        $binary = config('services.ytdlp.binary', 'yt-dlp');

        return array_merge(
            [$binary],
            ['--no-playlist', '--newline', '--no-overwrites'],
            $this->formatOptions($download),
            ['-o', $outputTemplate],
            [$download->url],
        );
    }

    /**
     * @return list<string>
     */
    private function formatOptions(Download $download): array
    {
        if ($download->format === 'mp3') {
            return ['-x', '--audio-format', 'mp3'];
        }

        return match ($download->quality) {
            '720p' => ['-f', 'bv*[height<=720]+ba/b[height<=720]/b'],
            '1080p' => ['-f', 'bv*[height<=1080]+ba/b[height<=1080]/b'],
            default => ['-f', 'bv*+ba/b'],
        };
    }

    private function directoryFor(Download $download): string
    {
        return "downloads/{$download->id}";
    }

    private function resolveOutputFile(string $directory): string
    {
        $files = Storage::disk('local')->files($directory);

        if ($files === []) {
            throw new YtDlpException('No output file was produced by yt-dlp.');
        }

        if (count($files) === 1) {
            return $files[0];
        }

        usort($files, fn (string $a, string $b) => Storage::disk('local')->size($b) <=> Storage::disk('local')->size($a));

        return $files[0];
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
}
