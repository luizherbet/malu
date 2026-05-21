<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'app_name' => config('app.name'),
                'require_auth' => config('malu.require_auth'),
                'allow_registration' => config('malu.allow_registration'),
            ],
        ]);
    }
}
