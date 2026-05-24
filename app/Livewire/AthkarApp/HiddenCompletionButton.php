<?php

declare(strict_types=1);

namespace App\Livewire\AthkarApp;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

class HiddenCompletionButton extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function completionAction(): Action
    {
        return Action::make('completion')
            ->requiresConfirmation()
            ->action(fn () => $this->js('markAllActiveModeComplete()'))
            ->modalIconColor('warning')
            ->label('إكمال الأذكار')
            ->modalDescription('هل قمت بقراءة كل الأذكار مسبقًا، وتريد أن تجعلها كلها مقروءة بشكل تلقائي؟')
            ->modalSubmitActionLabel('قرأتها')
            ->modalCancelActionLabel('لم أقرأها بعد')
            ->extraModalWindowAttributes([
                'id' => 'athkar-hidden-completion-modal',
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ]);
    }

    public function singleThikrCompletionAction(): Action
    {
        return Action::make('singleThikrCompletion')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $index = (int) ($arguments['index'] ?? -1);

                if ($index < 0) {
                    return;
                }

                $this->js(
                    "window.dispatchEvent(new CustomEvent('athkar-single-completion-confirmed', { detail: { index: {$index} } }))",
                );
            })
            ->modalIconColor('warning')
            ->label('إتمام الذكر')
            ->modalDescription('هل أتممت قراءة هذا الذكر بعدده كاملا؟')
            ->modalSubmitActionLabel('نعم، أكملت قراءته')
            ->modalCancelActionLabel('إلغاء')
            ->extraModalWindowAttributes([
                'id' => 'athkar-hidden-single-completion-modal',
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ]);
    }

    public function render()
    {
        return view('livewire.athkar-app.hidden-completion-button');
    }
}
