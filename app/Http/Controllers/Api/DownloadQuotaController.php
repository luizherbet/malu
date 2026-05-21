<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DownloadQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DownloadQuotaController extends Controller
{
    public function __invoke(Request $request, DownloadQuotaService $quota): JsonResponse
    {
        return response()->json([
            'data' => $quota->snapshot($request->user()?->id),
        ]);
    }
}
