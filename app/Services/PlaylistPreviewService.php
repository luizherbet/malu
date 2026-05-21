<?php

namespace App\Services;

use App\Exceptions\YtDlpException;
use Illuminate\Support\Facades\Process;

class PlaylistPreviewService
{
    public function __construct(private YtDlpService $ytDlp) {}

    /**
     * @return list<array{index: int, id: string, title: string, url: string, section: string|null}>
     */
    public function listTracks(string $url): array
    {
        if ($this->ytDlp->isMultiVideoPlaylist($url)) {
            return $this->listPlaylistVideos($url);
        }

        return $this->listSingleVideoEntries($url);
    }

    /**
     * @return list<array{index: int, id: string, title: string, url: string, section: string|null}>
     */
    private function listPlaylistVideos(string $url): array
    {
        $command = array_merge(
            [$this->binary()],
            $this->ytDlp->extractorArguments(),
            ['--flat-playlist', '-j', '--no-warnings', '--no-download', '--ignore-errors'],
            [$url],
        );

        $result = Process::timeout($this->previewTimeout())->run($command);

        if ($result->failed()) {
            throw YtDlpException::fromProcess(
                $result->exitCode() ?? 1,
                $result->errorOutput() ?: $result->output(),
            );
        }

        return $this->parseFlatPlaylistLines($result->output());
    }

    /**
     * @return list<array{index: int, id: string, title: string, url: string, section: string|null}>
     */
    private function listSingleVideoEntries(string $url): array
    {
        $command = array_merge(
            [$this->binary()],
            $this->ytDlp->extractorArguments(),
            ['-j', '--no-warnings', '--no-download', '--no-playlist'],
            [$url],
        );

        $result = Process::timeout($this->previewTimeout())->run($command);

        if ($result->failed()) {
            throw YtDlpException::fromProcess(
                $result->exitCode() ?? 1,
                $result->errorOutput() ?: $result->output(),
            );
        }

        $payload = json_decode(trim($result->output()), true);

        if (! is_array($payload)) {
            throw new YtDlpException('Could not parse video metadata from yt-dlp.');
        }

        $videoId = (string) ($payload['id'] ?? '');
        $videoUrl = (string) ($payload['webpage_url'] ?? $payload['original_url'] ?? $url);
        $videoTitle = (string) ($payload['title'] ?? 'Untitled');

        $chapters = $payload['chapters'] ?? null;

        if (is_array($chapters) && $chapters !== []) {
            return $this->mapChapters($chapters, $videoId, $videoUrl);
        }

        return [[
            'index' => 1,
            'id' => $videoId !== '' ? $videoId : 'video',
            'title' => $videoTitle,
            'url' => $videoUrl,
            'section' => null,
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $chapters
     * @return list<array{index: int, id: string, title: string, url: string, section: string|null}>
     */
    private function mapChapters(array $chapters, string $videoId, string $videoUrl): array
    {
        $tracks = [];
        $index = 1;

        foreach ($chapters as $chapter) {
            if (! is_array($chapter)) {
                continue;
            }

            $title = trim((string) ($chapter['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $tracks[] = [
                'index' => $index,
                'id' => $videoId !== '' ? "{$videoId}-{$index}" : "chapter-{$index}",
                'title' => $title,
                'url' => $videoUrl,
                'section' => $title,
            ];

            $index++;
        }

        if ($tracks === []) {
            throw new YtDlpException('No chapters found in this video.');
        }

        return $tracks;
    }

    /**
     * @return list<array{index: int, id: string, title: string, url: string, section: string|null}>
     */
    private function parseFlatPlaylistLines(string $output): array
    {
        $tracks = [];
        $index = 1;

        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $entry = json_decode($line, true);

            if (! is_array($entry)) {
                continue;
            }

            $id = (string) ($entry['id'] ?? '');

            if ($id === '' || $id === 'None') {
                continue;
            }

            $title = trim((string) ($entry['title'] ?? 'Untitled'));
            $trackUrl = (string) ($entry['url'] ?? $entry['webpage_url'] ?? '');
            $trackUrl = $this->normalizeVideoUrl($trackUrl, $id);

            $tracks[] = [
                'index' => $index,
                'id' => $id,
                'title' => $title,
                'url' => $trackUrl,
                'section' => null,
            ];

            $index++;
        }

        if ($tracks === []) {
            throw new YtDlpException('No videos found in this playlist.');
        }

        return $tracks;
    }

    private function normalizeVideoUrl(string $url, string $id): string
    {
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return "https://www.youtube.com/watch?v={$id}";
    }

    private function binary(): string
    {
        return config('services.ytdlp.binary', 'yt-dlp');
    }

    private function previewTimeout(): int
    {
        return (int) config('services.ytdlp.preview_timeout', 120);
    }
}
