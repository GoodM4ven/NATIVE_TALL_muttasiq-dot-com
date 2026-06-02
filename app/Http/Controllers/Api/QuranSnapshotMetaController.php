<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Native\NativeQuranSnapshotApiService;
use Illuminate\Http\JsonResponse;

class QuranSnapshotMetaController extends Controller
{
    public function __invoke(NativeQuranSnapshotApiService $snapshotApiService): JsonResponse
    {
        return response()->json([
            'snapshot' => $snapshotApiService->metadata(),
        ]);
    }
}
