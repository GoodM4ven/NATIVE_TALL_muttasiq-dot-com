<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class MainMenuIntroductionVideoButton extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function openIntroductionVideoAction(): Action
    {
        return Action::make('openIntroductionVideo')
            ->modalHeading(arabic_text('ما هو متسق؟'))
            ->modalDescription(arabic_text('شاهد مقطعًا مصوّرًا يشرح عمل مميزّات المنصة وخصائصها حتى الآن...'))
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'class' => 'muttasiq-modal-window introduction-video-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay introduction-video-modal-overlay',
            ])
            ->modalContent(fn (): HtmlString => new HtmlString(
                Blade::render(
                    '<x-partials.introduction-video-modal :embed-url="$embedUrl" :video-url="$videoUrl" />',
                    [
                        'embedUrl' => Setting::youtubeVideoEmbedUrl(),
                        'videoUrl' => Setting::youtubeVideoUrl(),
                    ],
                ),
            ))
            ->action(static fn (): null => null);
    }

    public function render(): View
    {
        return view('livewire.main-menu-introduction-video-button');
    }
}
