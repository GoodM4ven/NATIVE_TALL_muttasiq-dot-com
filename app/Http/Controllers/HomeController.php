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

        $baseUrl = trim((string) config('services.athkar.base_url', 'https://muttasiq.com'));

        if ($baseUrl === '') {
            return [];
        }

        $url = rtrim($baseUrl, '/').'/api/athkar';

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.athkar.timeout', 8))
                ->get($url);

            if ($response->successful()) {
                $athkar = $response->json('athkar');

                return is_array($athkar) ? $athkar : [];
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

        return [];
    }
}
