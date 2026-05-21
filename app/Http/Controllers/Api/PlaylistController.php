<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\YtDlpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewPlaylistRequest;
use App\Services\PlaylistPreviewService;
use Illuminate\Http\JsonResponse;

class PlaylistController extends Controller
{
    public function preview(PreviewPlaylistRequest $request, PlaylistPreviewService $preview): JsonResponse
    {
        try {
            $tracks = $preview->listTracks($request->validated('url'));
        } catch (YtDlpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => [
                'source_url' => $request->validated('url'),
                'tracks' => $tracks,
            ],
        ]);
    }
}
