<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Thikr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        return view('home', [
            'athkar' => $this->resolveAthkarPayload(),
            'athkarSettings' => Setting::query()
                ->whereIn('name', array_keys(Setting::defaults()))
                ->pluck('value', 'name')
                ->all(),
        ]);
    }

    /**
     * @return array<int, array{id: int, time: string, text: string, count: int, order: int}>
     */
    private function resolveAthkarPayload(): array
    {
        if (! is_platform('mobile')) {
            return Thikr::defaultsPayload();
        }

        $url = route('api.athkar.index');
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::acceptJson()
                ->timeout((int) config('app.custom.native_end_points.retries', 8))
                ->get($url);

            if ($response->successful()) {
                if (is_array($athkar = $response->json('athkar'))) {
                    return $athkar;
                }

                Log::warning('Athkar API returned an invalid payload.', [
                    'url' => $url,
                ]);
            }

            Log::warning('Athkar API returned non-success response.', [
                'status' => $response->status(),
                'url' => $url,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Athkar API request failed.', [
                'message' => $exception->getMessage(),
                'url' => $url,
            ]);
        }

        return Thikr::defaultsPayload();
    }
}
