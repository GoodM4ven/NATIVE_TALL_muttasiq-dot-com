<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Traits\HasControlPanelChangelogsTab;
use Illuminate\View\View;
use Livewire\Component;

class ControlPanelChangelogs extends Component
{
    use HasControlPanelChangelogsTab;

    public function placeholder(): string
    {
        return view('livewire.control-panel-changelogs-placeholder')->render();
    }

    public function render(): View
    {
        return view('livewire.control-panel-changelogs', [
            'changelogsHtml' => $this->changelogsMarkdown(),
        ]);
    }
}
