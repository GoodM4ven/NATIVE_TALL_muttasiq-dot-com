<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class NativeBroadcastAuthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(is_platform('native'), 404);

        $user = Auth::user();
        $serverBase = native_server_base();

        if (! $user instanceof User || blank($user->native_api_token) || $serverBase === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
        ]);

        $response = Http::asJson()->acceptJson()
            ->connectTimeout(3)->timeout(5)
            ->withToken((string) $user->native_api_token)
            ->post($serverBase.'/api/broadcasting/auth', $validated);

        return response()->json(
            $response->json() ?? [],
            $response->status(),
        );
    }
}
