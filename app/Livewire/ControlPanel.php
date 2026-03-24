<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use App\Services\Traits\HasControlPanelAboutTab;
use App\Services\Traits\HasControlPanelChangelogsTab;
use App\Services\Traits\HasControlPanelSettingsTab;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class ControlPanel extends Component implements HasActions, HasSchemas
{
    use HasControlPanelAboutTab;
    use HasControlPanelChangelogsTab;
    use HasControlPanelSettingsTab;
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const CONTROL_PANEL_TAB_INDEX = 1;

    private const UPDATES_TAB_INDEX = 2;

    /**
     * @var array<string, bool|int>
     */
    public array $clientControlPanel = [];

    public int $controlPanelActiveTab = self::CONTROL_PANEL_TAB_INDEX;

    public function controlPanelAction(): Action
    {
        return Action::make('controlPanel')
            ->label('لوحة التحكم')
            ->modalDescription('بعض المعلومات والتفضيلات في كيفية عمل التطبيق')
            ->modalSubmitActionLabel('حفظ')
            ->extraModalWindowAttributes([
                'id' => 'control-panel-modal',
            ])
            ->fillForm(fn (): array => $this->loadControlPanel())
            ->schema([
                Tabs::make('Tabs')
                    ->activeTab(fn (): int => $this->controlPanelActiveTab)
                    ->tabs([
                        $this->controlPanelSettingsTab(),
                        $this->controlPanelChangelogsTab(),
                        $this->controlPanelAboutTab(),
                    ]),
            ])
            ->action(function (array $data): void {
                if (is_array($data[self::MAIN_TEXT_SIZE_RANGE] ?? null)) {
                    $rangeValues = array_values($data[self::MAIN_TEXT_SIZE_RANGE]);
                    $minimumSize = (int) ($rangeValues[0] ?? Setting::MIN_MAIN_TEXT_SIZE_DEFAULT);
                    $maximumSize = (int) ($rangeValues[1] ?? Setting::MAX_MAIN_TEXT_SIZE_DEFAULT);
                    $data[Setting::MINIMUM_MAIN_TEXT_SIZE] = min($minimumSize, $maximumSize);
                    $data[Setting::MAXIMUM_MAIN_TEXT_SIZE] = max($minimumSize, $maximumSize);
                }

                $savedControlPanel = Setting::normalizeSettings($data);
                $isMaintenancePulse = $this->isMountedControlPanelMaintenancePulse();

                $this->clientControlPanel = $savedControlPanel;
                $this->dispatch(
                    'control-panel-updated',
                    controlPanel: $savedControlPanel,
                    maintenancePulse: $isMaintenancePulse,
                );

                if (! $isMaintenancePulse) {
                    notify(iconName: 'mdi.content-save-check', title: 'تم حفظ الإعدادات بنجاح');
                }
            });
    }

    public function triggerReaderMaintenancePulse(): void
    {
        $normalizedControlPanel = $this->filterControlPanel($this->loadControlPanel());

        $this->syncClientControlPanel($normalizedControlPanel);
        $this->dispatch(
            'control-panel-updated',
            controlPanel: $normalizedControlPanel,
            maintenancePulse: true,
        );

        $this->runSaveLikeControlPanelPulse();
        $this->dispatch(
            'control-panel-updated',
            controlPanel: $this->clientControlPanel,
            maintenancePulse: true,
        );

        $this->forceRender();
    }

    public function setControlPanelActiveTab(?string $tab = null): void
    {
        $this->controlPanelActiveTab = $tab === 'updates'
            ? self::UPDATES_TAB_INDEX
            : self::CONTROL_PANEL_TAB_INDEX;
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     */
    public function openControlPanelModal(array $controlPanel = [], ?string $tab = null): void
    {
        $this->syncClientControlPanel($controlPanel);
        $this->setControlPanelActiveTab($tab);
        $this->mountAction('controlPanel');
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     */
    public function syncClientControlPanel(array $controlPanel): void
    {
        $this->clientControlPanel = $this->filterControlPanel($controlPanel);
    }

    public function render(): View
    {
        return view('livewire.control-panel');
    }

    /**
     * @return array<string, bool|int|list<int>>
     */
    private function loadControlPanel(): array
    {
        $storedControlPanelValues = Setting::query()
            ->whereIn('name', array_keys(self::controlPanelDefaults()))
            ->pluck('value', 'name')
            ->all();

        $normalizedControlPanelValues = Setting::normalizeSettings(
            array_replace(self::controlPanelDefaults(), $storedControlPanelValues, $this->clientControlPanel),
        );

        $normalizedControlPanelValues[self::MAIN_TEXT_SIZE_RANGE] = [
            (int) ($normalizedControlPanelValues[Setting::MINIMUM_MAIN_TEXT_SIZE] ?? Setting::MIN_MAIN_TEXT_SIZE_DEFAULT),
            (int) ($normalizedControlPanelValues[Setting::MAXIMUM_MAIN_TEXT_SIZE] ?? Setting::MAX_MAIN_TEXT_SIZE_DEFAULT),
        ];

        return $normalizedControlPanelValues;
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     * @return array<string, bool|int>
     */
    private function filterControlPanel(array $controlPanel): array
    {
        return Setting::normalizeSettings(
            array_intersect_key($controlPanel, self::controlPanelDefaults()),
        );
    }

    private function runSaveLikeControlPanelPulse(): void
    {
        try {
            $this->mountAction('controlPanel', ['maintenancePulse' => true]);

            if (! $this->getMountedAction()) {
                return;
            }

            $this->callMountedAction();
        } catch (Throwable) {
            if (count($this->mountedActions ?? [])) {
                $this->unmountAction(canCancelParentActions: false);
            }
        }
    }

    private function isMountedControlPanelMaintenancePulse(): bool
    {
        $mountedActions = $this->mountedActions ?? [];

        if ($mountedActions === []) {
            return false;
        }

        $mountedAction = $mountedActions[array_key_last($mountedActions)] ?? null;

        if (! is_array($mountedAction)) {
            return false;
        }

        $arguments = $mountedAction['arguments'] ?? [];
        $value = $arguments['maintenancePulse'] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        return false;
    }
}
