<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitMetricRequest;
use App\Services\Monitoring\WebHomeActivityTracker;
use App\Services\Support\Enums\ViewName;
use Illuminate\Http\JsonResponse;

class VisitMetricController extends Controller
{
    public function __invoke(StoreVisitMetricRequest $request, WebHomeActivityTracker $tracker): JsonResponse
    {
        $context = $this->resolveContext($request->validated('view'));

        if ($context === null) {
            return response()->json([
                'message' => 'تم تجاهل الزيارة.',
            ]);
        }

        $tracker->track($request, $context);

        return response()->json([
            'message' => 'تم استلام الزيارة بنجاح.',
        ]);
    }

    private function resolveContext(string $view): ?string
    {
        return match ($view) {
            ViewName::MainMenu->value => WebHomeActivityTracker::CONTEXT_HOME,
            ViewName::AthkarAppGate->value,
            ViewName::AthkarAppSabah->value,
            ViewName::AthkarAppMasaa->value => WebHomeActivityTracker::CONTEXT_ATHKAR_GATE,
            ViewName::QuranAppGate->value,
            ViewName::QuranAppTilawa->value,
            ViewName::QuranAppHifth->value,
            ViewName::QuranAppTadabbur->value => WebHomeActivityTracker::CONTEXT_QURAN_GATE,
            default => null,
        };
    }
}
