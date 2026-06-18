<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Monitoring\WebHomeActivityTracker;
use App\Services\Native\NativeVisitMetricsRelay;
use App\Services\Support\Enums\ViewName;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WebHomeViewTracker extends Component
{
    public ?string $currentAppContext = null;

    public function trackAppView(?string $view): void
    {
        if (! $this->shouldTrack()) {
            return;
        }

        $context = $this->resolveAppContext($view);

        if ($context === null) {
            $this->currentAppContext = null;

            return;
        }

        if ($context === WebHomeActivityTracker::CONTEXT_HOME) {
            $this->currentAppContext = null;

            if (is_platform('native')) {
                app(NativeVisitMetricsRelay::class)->relay($view, request());
            } else {
                app(WebHomeActivityTracker::class)->track(request(), $context);
            }

            return;
        }

        if ($context === $this->currentAppContext) {
            return;
        }

        if (is_platform('native')) {
            app(NativeVisitMetricsRelay::class)->relay($view, request());
        } else {
            app(WebHomeActivityTracker::class)->track(request(), $context);
        }

        $this->currentAppContext = $context;
    }

    public function trackGateView(?string $view): void
    {
        $this->trackAppView($view);
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

        return is_platform('web') || is_platform('native');
    }

    private function resolveAppContext(?string $view): ?string
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
