<?php

namespace App\Http\Controllers\Api;

use App\Enums\DownloadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDownloadRequest;
use App\Http\Resources\DownloadResource;
use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use App\Support\MediaUrlValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function store(StoreDownloadRequest $request): JsonResponse
    {
        $download = Download::create($request->validated());

        ProcessDownloadJob::dispatch($download);

        return DownloadResource::make($download)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Download $download): DownloadResource
    {
        return DownloadResource::make($download);
    }

    public function file(Download $download, Request $request): StreamedResponse
    {
        if ($download->status !== DownloadStatus::Done) {
            abort(404, 'File is not ready.');
        }

        $path = $this->resolveFilePath($download, $request);

        if (! MediaUrlValidator::isSafeStoragePath($path) || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        $fileName = basename($path);
        $fileName = preg_replace('/[^\w.\-()+ ]/u', '_', $fileName) ?: 'track.mp3';

        return Storage::disk('local')->download($path, $fileName);
    }

    private function resolveFilePath(Download $download, Request $request): string
    {
        if ($download->download_playlist) {
            $track = $request->query('track');

            if (! is_string($track) || $track === '') {
                abort(400, 'Informe o parâmetro track para baixar uma faixa da playlist.');
            }

            $track = basename($track);

            return "downloads/{$download->id}/{$track}";
        }

        if ($download->file_path === null) {
            abort(404, 'File is not ready.');
        }

        return $download->file_path;
    }
}
