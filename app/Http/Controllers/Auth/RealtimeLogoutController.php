<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\WebSessionDevices;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RealtimeLogoutController extends Controller
{
    public function __invoke(WebSessionDevices $webSessionDevices): JsonResponse
    {
        $webSessionDevices->remove(session()->getId());

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
