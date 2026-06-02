<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Monitoring\WebHomeActivityTracker;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WebHomeViewTracker extends Component
{
    public function trackGateView(?string $view): void
    {
        if (! $this->shouldTrack()) {
            return;
        }

        $context = match ($view) {
            WebHomeActivityTracker::CONTEXT_ATHKAR_GATE => WebHomeActivityTracker::CONTEXT_ATHKAR_GATE,
            WebHomeActivityTracker::CONTEXT_QURAN_GATE => WebHomeActivityTracker::CONTEXT_QURAN_GATE,
            default => null,
        };

        if ($context === null) {
            return;
        }

        app(WebHomeActivityTracker::class)->track(request(), $context);
    }

    public function render(): View
    {
        return view('livewire.web-home-view-tracker');
    }

    private function shouldTrack(): bool
    {
        if (! (bool) config('app.custom.security.web_home_metrics.enabled', false)) {
            return false;
        }

        return is_platform('web');
    }
}
