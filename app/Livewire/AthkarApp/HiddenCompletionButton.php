<?php

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
            ->modalCancelActionLabel('لم أقرأها بعد');
    }

    public function render()
    {
        return view('livewire.athkar-app.hidden-completion-button');
    }
}
