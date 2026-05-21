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

    public function file(Download $download): StreamedResponse
    {
        if ($download->status !== DownloadStatus::Done || $download->file_path === null) {
            abort(404, 'File is not ready.');
        }

        if (! MediaUrlValidator::isSafeStoragePath($download->file_path)) {
            abort(404, 'File not found.');
        }

        if (! Storage::disk('local')->exists($download->file_path)) {
            abort(404, 'File not found.');
        }

        $fileName = basename($download->file_path);
        $fileName = preg_replace('/[^\w.\-()+ ]/u', '_', $fileName) ?: 'download';

        return Storage::disk('local')->download($download->file_path, $fileName);
    }
}
