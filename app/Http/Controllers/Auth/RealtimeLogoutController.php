<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RealtimeLogoutController extends Controller
{
    public function __invoke(): JsonResponse
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
